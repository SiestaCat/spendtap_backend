<?php

namespace App\Controller;

use App\Entity\Spent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/api/spent')]
class DescriptionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    private function checkAuthentication(Request $request): ?JsonResponse
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

    #[Route('/last_descriptions', name: 'get_last_descriptions', methods: ['GET'])]
    public function getDescriptions(Request $request): JsonResponse
    {
        $authResponse = $this->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        $limit = $request->query->get('limit', 5);
        $limit = max(1, min(100, (int) $limit)); // Between 1 and 100

        try {
            $descriptions = $this->entityManager
                ->createQueryBuilder()
                ->select('DISTINCT s.description')
                ->from(Spent::class, 's')
                ->where('s.description IS NOT NULL')
                ->orderBy('s.id', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getArrayResult();

            $result = array_column($descriptions, 'description');

            return new JsonResponse([
                'descriptions' => array_values(array_unique($result)),
                'count' => count($result),
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch descriptions'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}