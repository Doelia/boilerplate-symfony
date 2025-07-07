<?php

namespace App\Tests\HttpTests;

use App\Attributes\HttpTest;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use RecursiveIteratorIterator;
use ReflectionMethod;
use Symfony\Component\Routing\Attribute\Route;

class AttributeHttpTest extends WebTestCase
{
    #[DataProvider('provideHttpTests')]
    public function testHttpTestAttribute(string $className, ReflectionMethod $method, HttpTest $testAttr): void
    {
        $client = self::createClient();

        // 1) Exécuter éventuel SQL avant
        $this->executePreRequestSql($testAttr, $client);

        // 2) Exécuter éventuel pré-test
        $preRequest_response = $this->executePreTest($testAttr, $client);

        // 3) Construire la requête finale
        $request = $this->buildRequest($method, $testAttr, $preRequest_response);

        // 4) Exécuter la requête
        if ($request['json'] !== null) {
            $client->request(
                $request['method'],
                $request['url'],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($request['json'])
            );
        } else {
            $client->request($request['method'], $request['url']);
        }

        // 5) Vérifier le statut
        $statusCode = $client->getResponse()->getStatusCode();
        $content = $client->getResponse()->getContent();

        $this->assertSame(
            $testAttr->status ?? 200,
            $statusCode,
            sprintf(
                "Failed on %s (%s::%s) : HTTP %s\n%s",
                $request['url'],
                $method->getDeclaringClass()->getName(),
                $method->getName(),
                $statusCode,
                $content
            )
        );
    }

    public static function provideHttpTests(): iterable
    {
        $controllerDir = __DIR__ . '/../../src/Controller';
        $files = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllerDir));

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if (!str_contains(file_get_contents($file->getPathname()), '#[HttpTest')) {
                continue;
            }

            $relativePath = str_replace($controllerDir . '/', '\\App\\Controller\\', $file->getPathname());
            $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $rc = new \ReflectionClass($className);

            foreach ($rc->getMethods() as $method) {
                $idx = 0;
                foreach ($method->getAttributes(HttpTest::class) as $attr) {
                    $testAttr = $attr->newInstance();
                    $name = $testAttr->name ?: "{$className}::{$method->getName()}.{$idx}";
                    yield $name => [$className, $method, $testAttr];
                    $idx++;
                }
            }
        }
    }

    private function executePreRequestSql(HttpTest $testAttr, $client): void
    {
        if (empty($testAttr->preRequestSQL)) {
            return;
        }

        $sql = $testAttr->preRequestSQL;
        $client->getContainer()->get('doctrine')->getConnection()->executeStatement($sql);
    }

    private function executePreTest(HttpTest $testAttr, $client): ?array
    {
        if (empty($testAttr->preTest)) {
            return null;
        }

        [$preMethod, $preAttr] = $this->getPreTestMethodAndAttr($testAttr->preTest);

        $preRequest = $this->buildRequest($preMethod, $preAttr);

        if ($preRequest['json'] !== null) {
            $client->request(
                $preRequest['method'],
                $preRequest['url'],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($preRequest['json'])
            );
        } else {
            $client->request($preRequest['method'], $preRequest['url']);
        }

        if (!$client->getResponse()->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                "PreTest '%s' failed: %s %s => HTTP %d\n%s",
                $testAttr->preTest,
                $preRequest['method'],
                $preRequest['url'],
                $client->getResponse()->getStatusCode(),
                $client->getResponse()->getContent()
            ));
        }

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function getPreTestMethodAndAttr(string $name): array
    {
        foreach ($this->provideHttpTests() as [$className, $method, $httpTest]) {
            if ($httpTest->name === $name) {
                return [$method, $httpTest];
            }
        }

        throw new \InvalidArgumentException("PreTest with name '{$name}' not found.");
    }

    private function buildRequest(
        ReflectionMethod $method,
        HttpTest $testAttr,
        ?array $preRequest_response = null
    ): array {

        // 1) Récupérer le préfixe de la classe s'il existe
        $classRouteAttr = $method->getDeclaringClass()->getAttributes(Route::class);
        $classPath = '';
        if (!empty($classRouteAttr)) {
            $routeInstance = $classRouteAttr[0]->newInstance();
            $path = $routeInstance->getPath();
            $classPath = $path !== null ? rtrim($path, '/') : '';
        }

        // 2) Récupérer la route de la méthode
        $methodRouteAttr = $method->getAttributes(Route::class);

        if (empty($methodRouteAttr)) {
            throw new \RuntimeException(sprintf(
                "No #[Route] found on method %s::%s",
                $method->getDeclaringClass()->getName(),
                $method->getName()
            ));
        }
        $methodPath = $methodRouteAttr[0]->newInstance()->getPath();
        $methodPath = '/' . ltrim($methodPath, '/');

        // 3) Concaténer
        $url = $classPath . $methodPath;

        // 4) Remplacer les paramètres de chemin
        foreach ($testAttr->pathParams as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        // 5) Ajouter les query params
        foreach ($testAttr->queryParams as $key => $value) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . urlencode($key) . '=' . urlencode($value);
        }

        // 6) Remplacer les placeholders dynamiques
        if ($preRequest_response !== null) {
            $url = $this->replacePlaceholders($url, ['preRequest' => $preRequest_response]);
        }

        // 7) Déterminer la méthode HTTP
        $httpMethod = $methodRouteAttr[0]->newInstance()->getMethods()[0] ?? 'GET';

        return [
            'method' => $httpMethod,
            'url'    => $url,
            'json'   => $testAttr->json,
        ];
    }


    private function replacePlaceholders(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{(.*?)}}/', function ($matches) use ($context) {
            $path = trim($matches[1]);
            $parts = explode('.', $path);

            $value = $context;
            foreach ($parts as $part) {
                if (is_array($value) && array_key_exists($part, $value)) {
                    $value = $value[$part];
                } else {
                    throw new \RuntimeException("Placeholder {{$path}} not found in context: " . json_encode($context));
                }
            }

            return $value;
        }, $template);
    }
}
