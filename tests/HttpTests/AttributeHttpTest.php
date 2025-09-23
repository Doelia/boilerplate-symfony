<?php

namespace App\Tests\HttpTests;

use App\Attributes\HttpTest;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
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
        $preSqlRequest_response = $this->executePreRequestSql($testAttr, $client);

        // 2) Exécuter éventuel pré-test
        $preTestRequest_response = $this->executePreTest($testAttr, $client);

        // 3) Construire la requête finale
        $request = $this->buildRequest($method, $testAttr, $preTestRequest_response, $preSqlRequest_response);

        // 4) Exécuter la requête
        if ($request['json'] !== null) {
            $client->request($request['method'], $request['url'], [], [], $request['headers'], json_encode($request['json']) ?: null);
        } else {
            $client->request($request['method'], $request['url'], [], [], $request['headers']);
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

            if (!str_contains(file_get_contents($file->getPathname()) ?: '', '#[HttpTest')) {
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

    private function executePreRequestSql(HttpTest $testAttr, KernelBrowser $client): array
    {
        if (empty($testAttr->preRequestSQL)) {
            return [];
        }

        $sql = $testAttr->preRequestSQL;

        /**
         * @var Connection $db
         */
        $db = $client->getContainer()->get('doctrine')->getConnection();

        return $db->fetchAllAssociative($sql);
    }

    private function executePreTest(HttpTest $testAttr, KernelBrowser $client): ?array
    {
        if (empty($testAttr->preTest)) {
            return null;
        }

        [$preMethod, $preAttr] = $this->getPreTestMethodAndAttr($testAttr->preTest);

        $preRequest = $this->buildRequest($preMethod, $preAttr);

        if ($preRequest['json'] !== null) {
            $client->request($preRequest['method'], $preRequest['url'], [], [], $preRequest['headers'], json_encode($preRequest['json']) ?: null);
        } else {
            $client->request($preRequest['method'], $preRequest['url'], [], [], $preRequest['headers']);
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

        return json_decode($client->getResponse()->getContent(), true) ?: null;
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
        HttpTest         $testAttr,
        ?array           $preTestRequest_response = null,
        ?array           $preSqlRequest_response = null,
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
        if ($preTestRequest_response !== null) {
            $url = $this->replacePlaceholders($url, ['preRequest' => $preTestRequest_response]);
        }
        if ($preSqlRequest_response !== null) {
            $url = $this->replacePlaceholders($url, ['preRequestSql' => $preSqlRequest_response]);
        }

        // 7) Déterminer la méthode HTTP
        $httpMethod = $methodRouteAttr[0]->newInstance()->getMethods()[0] ?? 'GET';

        $headers = [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ];

        if ($testAttr->basicAuth !== null) {
            [$username, $password] = $testAttr->basicAuth;
            $headers['PHP_AUTH_USER'] = $username;
            $headers['PHP_AUTH_PW'] = $password;
            $headers['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode("$username:$password");
        }

        if ($testAttr->headers !== null) {
            foreach ($testAttr->headers as $key => $value) {
                $headers[$key] = $value;
            }
        }

        return [
            'method' => $httpMethod,
            'url'    => $url,
            'json'   => $testAttr->json,
            'headers' => $headers,
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
                    throw new \RuntimeException("Placeholder {{{$path}}} not found in context: " . json_encode($context));
                }
            }

            return $value;
        }, $template);
    }
}
