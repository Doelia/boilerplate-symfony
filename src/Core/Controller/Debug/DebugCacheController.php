<?php

namespace App\Core\Controller\Debug;

use App\Core\Attributes\HttpTest;
use App\Core\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;

#[Route(format: 'json')]
class DebugCacheController extends AbstractController
{
    #[HttpTest(queryParams: ['secret' => 'debugsecret_test'])]
    #[Route("/debug/cache")]
    public function debugCache(CacheService $cacheService): JsonResponse
    {
        $value = $cacheService->get('debug_cache', function (ItemInterface $item) {
            $item->expiresAfter(10);

            return 'generated at ' . (new \DateTimeImmutable())->format('H:i:s');
        });

        return $this->json([
            'value' => $value,
            'adapter' => $cacheService->getAdapter(),
        ]);
    }
}
