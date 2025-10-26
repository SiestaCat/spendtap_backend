<?php

namespace App\Controller;

use App\Entity\Spent;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/spent')]
class DescriptionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AuthService $authService;

    public function __construct(EntityManagerInterface $entityManager, AuthService $authService)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
    }

    #[Route('/last_descriptions', name: 'get_last_descriptions', methods: ['GET'])]
    public function getDescriptions(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
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

    #[Route('/all_descriptions', name: 'get_all_descriptions', methods: ['GET'])]
    public function getAllDescriptions(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        try {
            $descriptions = $this->entityManager
                ->createQueryBuilder()
                ->select('DISTINCT s.description')
                ->from(Spent::class, 's')
                ->where('s.description IS NOT NULL')
                ->orderBy('s.description', 'ASC')
                ->getQuery()
                ->getArrayResult();

            $result = array_column($descriptions, 'description');

            return new JsonResponse([
                'descriptions' => array_values($result),
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch all descriptions'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}