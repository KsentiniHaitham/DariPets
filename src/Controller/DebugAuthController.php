<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoint temporaire de diagnostic JWT — à supprimer après résolution.
 */
#[Route('/api/debug-auth', name: 'debug_auth', methods: ['GET'])]
class DebugAuthController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $auth = $request->headers->get('Authorization');
        $serverAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $redirectAuth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        $tokenPreview = null;
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
                $tokenPreview = [
                    'alg' => json_decode(base64_decode(str_pad(strtr($parts[0], '-_', '+/'), strlen($parts[0]) % 4, '=', STR_PAD_RIGHT)), true)['alg'] ?? '?',
                    'username' => $payload['username'] ?? $payload['sub'] ?? '?',
                    'roles' => $payload['roles'] ?? [],
                    'exp' => isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : '?',
                    'iat' => isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : '?',
                ];
            }
        }

        return new JsonResponse([
            'authorization_header' => $auth ?? 'NOT FOUND',
            'server_HTTP_AUTHORIZATION' => $serverAuth ?? 'NOT FOUND',
            'server_REDIRECT_HTTP_AUTHORIZATION' => $redirectAuth ?? 'NOT FOUND',
            'token_decoded' => $tokenPreview,
            'all_auth_keys' => array_filter(
                array_keys($_SERVER),
                fn($k) => str_contains(strtolower($k), 'auth')
            ),
        ]);
    }
}
