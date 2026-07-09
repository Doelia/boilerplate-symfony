<?php

declare(strict_types=1);

namespace App\Domains\Main\Controller;

use App\Core\Attributes\HttpTest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct()
    {
    }

    #[HttpTest]
    #[Route('/', name: 'app_index', format: 'json')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Index page',
        ]);
    }
}
