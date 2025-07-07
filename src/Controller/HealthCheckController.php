<?php

namespace App\Controller;

use App\Attributes\HttpTest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(format: 'json')]
class HealthCheckController extends AbstractController
{
    public function __construct()
    {
    }

    /**
     * Tester l'état de l'application, sans ses dépendances externes.
     * Ça permet juste de savoir si l'app est UP.
     * Un simple JSON code 200 suffit dans la plupart des cas.
     *
     * Ça peut être utilisé par l'orchestrateur. Il peut décider de reboot l'app si c'est DOWN.
     */
    #[HttpTest]
    #[Route(path: '/healthcheck')]
    public function healthcheck(): JsonResponse
    {
        return new JsonResponse(['status' => 'OK']);
    }

    /**
     * Tester l'état de l'application, avec ses dépendances externes.
     * Exemple : Consistance BDD, API externes, date de passage de la dernière cron, migrations SQL à jour...
     *
     * Peut être utilisé par du monitoring, pour aider à un diagnostic humain.
     */
    #[HttpTest]
    #[Route(path: '/healthcheck/deep')]
    public function deepHealthcheck(): JsonResponse
    {
        $healthcheck = $this->healthcheck();
        if ($healthcheck->getStatusCode() !== 200) {
            return $healthcheck;
        }

        return new JsonResponse(['status' => 'OK']);
    }
}
