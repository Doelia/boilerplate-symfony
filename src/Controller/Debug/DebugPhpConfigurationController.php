<?php

namespace App\Controller\Debug;

use App\Attributes\HttpTest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(format: 'json')]
class DebugPhpConfigurationController extends AbstractController
{
    #[HttpTest]
    #[Route("/debug/php-configuration")]
    public function debugPhpConfiguration(): JsonResponse
    {
        return $this->json([
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);
    }
}
