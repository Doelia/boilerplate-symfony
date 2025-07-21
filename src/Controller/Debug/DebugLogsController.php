<?php

namespace App\Controller\Debug;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Permet de vérifier le bon fonctionnement des logs
 */
#[Route(format: 'json')]
class DebugLogsController extends AbstractController
{
    #[Route('/debug/log-info')]
    public function logInfo(LoggerInterface $logger): JsonResponse
    {
        $logger->debug("debug_info/debug");
        $logger->info("debug_info/info");
        return $this->json([]);
    }

    #[Route('/debug/log-error')]
    public function logError(LoggerInterface $logger): JsonResponse
    {
        $logger->debug("debug_error/debug");
        $logger->info("debug_error/info");
        $logger->error("debug_error/error");
        return $this->json([]);
    }

    #[Route('/debug/log-deprecated')]
    public function logDeprecated(LoggerInterface $logger): JsonResponse
    {
        @trigger_error("This is a deprecated message", E_USER_DEPRECATED);
        $logger->error("log_deprecated/error");
        return $this->json([]);
    }
}
