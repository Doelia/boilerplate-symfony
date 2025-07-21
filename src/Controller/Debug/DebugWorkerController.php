<?php

namespace App\Controller\Debug;

use App\Attributes\HttpTest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(format: 'json')]
class DebugWorkerController extends AbstractController
{
    public static int $incr = 0;

    #[Route("/debug/worker/increment", name: "debug_worker_incr")]
    #[HttpTest]
    public function testIncrement(): JsonResponse
    {
        self::$incr++;
        return $this->json([
            'incr' => self::$incr,
        ]);
    }
}
