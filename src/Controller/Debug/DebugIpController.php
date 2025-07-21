<?php

namespace App\Controller\Debug;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(format: 'json')]
class DebugIpController extends AbstractController
{
    #[Route("/debug/ip")]
    public function debug(Request $request): JsonResponse
    {
        return new JsonResponse([
            'SERVER.REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '',
            'SERVER.HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            'SERVER.HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            'SERVER.PORT' => $_SERVER['SERVER_PORT'] ?? '',
            'SERVER.HTTPS' => $_SERVER['HTTPS'] ?? '',

            '$request.getClientIp()' => $request->getClientIp(),
            '$request.isFromTrustedProxy()' => $request->isFromTrustedProxy(), // est-ce REMOTE_ADDR est une adresse IP de confiance
            '$request->getPort()' => $request->getPort(),
            '$request->getSchemeAndHttpHost()' => $request->getSchemeAndHttpHost(),

            '$request->server->get(REMOTE_ADDR)' => $request->server->get('REMOTE_ADDR'),
            '$request->server->get(SERVER_PORT)' => $request->server->get('SERVER_PORT'),
            '$request->server->get(HTTPS)' => $request->server->get('HTTPS'),

            '$request->headers->get("CF-Connecting-IP")' => $request->headers->get("CF-Connecting-IP"),
            '$request->headers->get("X-Forwarded-For")' => $request->headers->get("X-Forwarded-For"),
            '$request->headers->get("X-Forwarded-Proto")' => $request->headers->get("X-Forwarded-Proto"),
            '$request->headers->get("X-Real-IP")' => $request->headers->get("X-Real-IP"),
            '$request->headers->get("X-Forwarded-Port")' => $request->headers->get("X-Forwarded-Port"),
            '$request->headers->get("X-Forwarded-Host")' => $request->headers->get("X-Forwarded-Host"),

        ]);
    }
}
