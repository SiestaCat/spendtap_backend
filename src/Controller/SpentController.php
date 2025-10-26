<?php

namespace App\Controller;

use App\Entity\Spent;
use App\Repository\SpentRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/spent')]
class SpentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SpentRepository $spentRepository;
    private AuthService $authService;

    public function __construct(EntityManagerInterface $entityManager, SpentRepository $spentRepository, AuthService $authService)
    {
        $this->entityManager = $entityManager;
        $this->spentRepository = $spentRepository;
        $this->authService = $authService;
    }

    #[Route('/create', name: 'create_spent', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        $spent = new Spent();

        if (isset($data['description'])) {
            $spent->setDescription($data['description']);
        }

        if (isset($data['category'])) {
            $spent->setCategory($data['category']);
        }

        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            return new JsonResponse(['error' => 'Amount is required and must be numeric'], Response::HTTP_BAD_REQUEST);
        }
        $spent->setAmount((string) $data['amount']);

        if (isset($data['date'])) {
            try {
                $date = new \DateTime($data['date']);
                $spent->setDate($date);
                
                // Only auto-calculate month/year if not explicitly provided
                if (!isset($data['month'])) {
                    $spent->setMonth((int) $date->format('n'));
                } 
                if (!isset($data['year'])) {
                    $spent->setYear((int) $date->format('Y'));
                }
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $now = new \DateTime();
            $spent->setDate($now);
            
            // Only auto-calculate month/year if not explicitly provided
            if (!isset($data['month'])) {
                $spent->setMonth((int) $now->format('n'));
            }
            if (!isset($data['year'])) {
                $spent->setYear((int) $now->format('Y'));
            }
        }

        // Set month if explicitly provided
        if (isset($data['month'])) {
            $month = (int) $data['month'];
            if ($month < 1 || $month > 12) {
                return new JsonResponse(['error' => 'Month must be between 1 and 12'], Response::HTTP_BAD_REQUEST);
            }
            $spent->setMonth($month);
        }

        // Set year if explicitly provided
        if (isset($data['year'])) {
            $year = (int) $data['year'];
            if ($year < 1900 || $year > 9999) {
                return new JsonResponse(['error' => 'Year must be between 1900 and 9999'], Response::HTTP_BAD_REQUEST);
            }
            $spent->setYear($year);
        }

        try {
            $this->entityManager->persist($spent);
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $spent->getId(),
                'description' => $spent->getDescription(),
                'category' => $spent->getCategory(),
                'amount' => $spent->getAmount(),
                'date' => $spent->getDate()->format('Y-m-d H:i:s'),
                'month' => $spent->getMonth(),
                'year' => $spent->getYear()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create spent entry'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/filter', name: 'filter_spent', methods: ['GET'])]
    public function filter(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        $month = $request->query->get('month');
        $year = $request->query->get('year');

        if (!$month || !$year) {
            return new JsonResponse(['error' => 'Month and year parameters are required'], Response::HTTP_BAD_REQUEST);
        }

        $month = (int) $month;
        $year = (int) $year;

        if ($month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Month must be between 1 and 12'], Response::HTTP_BAD_REQUEST);
        }

        if ($year < 1900 || $year > 9999) {
            return new JsonResponse(['error' => 'Year must be between 1900 and 9999'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $spentEntries = $this->entityManager->createQueryBuilder()
                ->select('s')
                ->from(Spent::class, 's')
                ->where('s.month = :month')
                ->andWhere('s.year = :year')
                ->setParameter('month', $month)
                ->setParameter('year', $year)
                ->orderBy('s.id', 'DESC')
                ->getQuery()
                ->getResult();

            $data = [];
            foreach ($spentEntries as $spent) {
                $data[] = [
                    'id' => $spent->getId(),
                    'description' => $spent->getDescription(),
                    'category' => $spent->getCategory(),
                    'amount' => $spent->getAmount(),
                    'date' => $spent->getDate()->format('Y-m-d H:i:s'),
                    'month' => $spent->getMonth(),
                    'year' => $spent->getYear()
                ];
            }

            return new JsonResponse([
                'data' => $data,
                'count' => count($data),
                'filters' => [
                    'month' => $month,
                    'year' => $year
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch spent entries'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', name: 'delete_spent', methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        try {
            $spent = $this->spentRepository->find($id);

            if (!$spent) {
                return new JsonResponse(['error' => 'Spent entry not found'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($spent);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Spent entry deleted successfully',
                'id' => $id
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete spent entry'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/edit/{id}', name: 'edit_spent', methods: ['PUT'])]
    public function edit(Request $request, int $id): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $spent = $this->spentRepository->find($id);

            if (!$spent) {
                return new JsonResponse(['error' => 'Spent entry not found'], Response::HTTP_NOT_FOUND);
            }

            // Update description if provided
            if (isset($data['description'])) {
                $spent->setDescription($data['description']);
            }

            // Update category if provided
            if (isset($data['category'])) {
                $spent->setCategory($data['category']);
            }

            // Update amount if provided
            if (isset($data['amount'])) {
                if (!is_numeric($data['amount'])) {
                    return new JsonResponse(['error' => 'Amount must be numeric'], Response::HTTP_BAD_REQUEST);
                }
                $spent->setAmount((string) $data['amount']);
            }

            // Update date if provided
            if (isset($data['date'])) {
                try {
                    $date = new \DateTime($data['date']);
                    $spent->setDate($date);
                    
                    // Only auto-calculate month/year if not explicitly provided
                    if (!isset($data['month'])) {
                        $spent->setMonth((int) $date->format('n'));
                    }
                    if (!isset($data['year'])) {
                        $spent->setYear((int) $date->format('Y'));
                    }
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
                }
            }

            // Update month if explicitly provided
            if (isset($data['month'])) {
                $month = (int) $data['month'];
                if ($month < 1 || $month > 12) {
                    return new JsonResponse(['error' => 'Month must be between 1 and 12'], Response::HTTP_BAD_REQUEST);
                }
                $spent->setMonth($month);
            }

            // Update year if explicitly provided
            if (isset($data['year'])) {
                $year = (int) $data['year'];
                if ($year < 1900 || $year > 9999) {
                    return new JsonResponse(['error' => 'Year must be between 1900 and 9999'], Response::HTTP_BAD_REQUEST);
                }
                $spent->setYear($year);
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $spent->getId(),
                'description' => $spent->getDescription(),
                'category' => $spent->getCategory(),
                'amount' => $spent->getAmount(),
                'date' => $spent->getDate()->format('Y-m-d H:i:s'),
                'month' => $spent->getMonth(),
                'year' => $spent->getYear()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update spent entry'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}