<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class DebugSecretListener
{
    public function __construct(
        #[Autowire(env: 'DEBUG_SECRETPASS')]
        private string $debugSecret,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/debug/')) {
            return;
        }

        if (empty($this->debugSecret) || $request->query->get('secret') !== $this->debugSecret) {
            $event->setResponse(new JsonResponse(['error' => 'Forbidden'], 403));
        }
    }
}
