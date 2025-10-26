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
use Psr\Log\LoggerInterface;

#[Route('/api/spent')]
class CategoryController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AuthService $authService;

    public function __construct(EntityManagerInterface $entityManager, AuthService $authService)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
    }

    #[Route('/last_categories', name: 'get_last_categories', methods: ['GET'])]
    public function getCategories(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        $limit = $request->query->get('limit', 5);
        $limit = max(1, min(100, (int) $limit)); // Between 1 and 100

        try {
            $categories = $this->entityManager
                ->createQueryBuilder()
                ->select('DISTINCT s.category')
                ->from(Spent::class, 's')
                ->where('s.category IS NOT NULL')
                ->orderBy('s.id', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getArrayResult();

            $result = array_column($categories, 'category');

            return new JsonResponse([
                'categories' => array_values(array_unique($result)),
                'count' => count($result),
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            throw $e; //throw $e; //return new JsonResponse(['error' => 'Failed to fetch categories'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/all_categories', name: 'get_all_categories', methods: ['GET'])]
    public function getAllCategories(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        try {
            $categories = $this->entityManager
                ->createQueryBuilder()
                ->select('DISTINCT s.category')
                ->from(Spent::class, 's')
                ->where('s.category IS NOT NULL')
                ->orderBy('s.category', 'ASC')
                ->getQuery()
                ->getArrayResult();

            $result = array_column($categories, 'category');

            return new JsonResponse([
                'categories' => array_values($result),
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            throw $e; //return new JsonResponse(['error' => 'Failed to fetch all categories'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}