<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthService
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function checkAuthentication(Request $request): ?JsonResponse
    {
        $providedToken = $request->headers->get('Authorization');
        $expectedToken = $this->parameterBag->get('app.api_token');
        
        if (!$providedToken || !str_starts_with($providedToken, 'Bearer ')) {
            return new JsonResponse(['error' => 'Authorization header required'], Response::HTTP_UNAUTHORIZED);
        }
        
        $token = substr($providedToken, 7); // Remove 'Bearer ' prefix
        
        if ($token !== $expectedToken) {
            return new JsonResponse(['error' => 'Invalid API token'], Response::HTTP_UNAUTHORIZED);
        }
        
        return null; // Authentication successful
    }
}