<?php

namespace App\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

class CacheService implements CacheInterface
{
    public function __construct(
        #[Autowire('%app_cache_adapter%')]
        private string $cacheAdapter,
        private CacheInterface $memoryCache,
        private CacheInterface $filesystemCache,
    ) {
    }

    public function getAdapter(): string
    {
        return $this->cacheAdapter;
    }

    private function getInterface(): CacheInterface
    {
        return match ($this->cacheAdapter) {
            'filesystem' => $this->filesystemCache,
            'memory' => $this->memoryCache,
            default => throw new \InvalidArgumentException("Unsupported cache adapter: " . $this->cacheAdapter),
        };
    }

    public function delete(string $key): bool
    {
        return $this->getInterface()->delete($key);
    }

    // On change le beta=null en beta=0 pour désactiver le système de lock de cache
    // Ça a causé des problèmes de lock de worker avec messenger en prod
    // beta = 0 => Si 50 requêtes arrivent en même temps sur un item expiré, les 50 vont déclencher le callback simultanément
    public function get(string $key, callable $callback, ?float $beta = 0, ?array &$metadata = null): mixed
    {
        return $this->getInterface()->get($key, $callback, $beta, $metadata);
    }
}
