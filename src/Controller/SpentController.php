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
        $categoriesParam = $request->query->get('categories', '');

        if (!$month || !$year) {
            return new JsonResponse(['error' => 'Month and year parameters are required'], Response::HTTP_BAD_REQUEST);
        }

        $month = (int) $month;
        $year = (int) $year;

        // Parse categories array
        $categories = [];
        if (!empty($categoriesParam)) {
            // Handle both comma-separated string and JSON array
            if (str_starts_with($categoriesParam, '[')) {
                $categories = json_decode($categoriesParam, true);
                if (!is_array($categories)) {
                    return new JsonResponse(['error' => 'Invalid categories format. Must be a JSON array'], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $categories = array_filter(array_map('trim', explode(',', $categoriesParam)));
            }
        }

        if ($month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Month must be between 1 and 12'], Response::HTTP_BAD_REQUEST);
        }

        if ($year < 1900 || $year > 9999) {
            return new JsonResponse(['error' => 'Year must be between 1900 and 9999'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $queryBuilder = $this->entityManager->createQueryBuilder()
                ->select('s')
                ->from(Spent::class, 's')
                ->where('s.month = :month')
                ->andWhere('s.year = :year')
                ->setParameter('month', $month)
                ->setParameter('year', $year);

            // Add category filter if categories are provided
            if (!empty($categories)) {
                $queryBuilder->andWhere('s.category IN (:categories)')
                            ->setParameter('categories', $categories);
            }

            $spentEntries = $queryBuilder
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
                    'year' => $year,
                    'categories' => $categories
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

    #[Route('/copy_month', name: 'copy_month_spent', methods: ['POST'])]
    public function copyMonth(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        // Validate required parameters
        if (!isset($data['source_month']) || !isset($data['source_year']) || 
            !isset($data['target_month']) || !isset($data['target_year'])) {
            return new JsonResponse(['error' => 'source_month, source_year, target_month, and target_year are required'], Response::HTTP_BAD_REQUEST);
        }

        $sourceMonth = (int) $data['source_month'];
        $sourceYear = (int) $data['source_year'];
        $targetMonth = (int) $data['target_month'];
        $targetYear = (int) $data['target_year'];
        $category = isset($data['category']) ? $data['category'] : null;

        // Validate month and year values
        if ($sourceMonth < 1 || $sourceMonth > 12 || $targetMonth < 1 || $targetMonth > 12) {
            return new JsonResponse(['error' => 'Months must be between 1 and 12'], Response::HTTP_BAD_REQUEST);
        }

        if ($sourceYear < 1900 || $sourceYear > 9999 || $targetYear < 1900 || $targetYear > 9999) {
            return new JsonResponse(['error' => 'Years must be between 1900 and 9999'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Build query for source entries
            $queryBuilder = $this->entityManager->createQueryBuilder()
                ->select('s')
                ->from(Spent::class, 's')
                ->where('s.month = :sourceMonth')
                ->andWhere('s.year = :sourceYear')
                ->setParameter('sourceMonth', $sourceMonth)
                ->setParameter('sourceYear', $sourceYear);

            // Add category filter if provided
            if ($category !== null) {
                $queryBuilder->andWhere('s.category = :category')
                            ->setParameter('category', $category);
            }

            $sourceEntries = $queryBuilder->getQuery()->getResult();

            if (empty($sourceEntries)) {
                return new JsonResponse([
                    'message' => 'No entries found to copy',
                    'copied_count' => 0,
                    'filters' => [
                        'source_month' => $sourceMonth,
                        'source_year' => $sourceYear,
                        'target_month' => $targetMonth,
                        'target_year' => $targetYear,
                        'category' => $category
                    ]
                ]);
            }

            $copiedCount = 0;
            $maxDayInTargetMonth = cal_days_in_month(CAL_GREGORIAN, $targetMonth, $targetYear);

            foreach ($sourceEntries as $sourceEntry) {
                $newSpent = new Spent();
                $newSpent->setDescription($sourceEntry->getDescription());
                $newSpent->setCategory($sourceEntry->getCategory());
                $newSpent->setAmount($sourceEntry->getAmount());
                $newSpent->setMonth($targetMonth);
                $newSpent->setYear($targetYear);

                // Smart date copying - adjust day if target month has fewer days
                $originalDate = $sourceEntry->getDate();
                $originalDay = (int) $originalDate->format('j');
                $adjustedDay = min($originalDay, $maxDayInTargetMonth);
                
                $newDate = new \DateTime();
                $newDate->setDate($targetYear, $targetMonth, $adjustedDay);
                $newDate->setTime(
                    (int) $originalDate->format('H'),
                    (int) $originalDate->format('i'),
                    (int) $originalDate->format('s')
                );
                
                $newSpent->setDate($newDate);

                $this->entityManager->persist($newSpent);
                $copiedCount++;
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Entries copied successfully',
                'copied_count' => $copiedCount,
                'filters' => [
                    'source_month' => $sourceMonth,
                    'source_year' => $sourceYear,
                    'target_month' => $targetMonth,
                    'target_year' => $targetYear,
                    'category' => $category
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to copy entries'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/breakdown_month', name: 'breakdown_month_spent', methods: ['GET'])]
    public function breakdownMonth(Request $request): JsonResponse
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
            $result = $this->entityManager->createQueryBuilder()
                ->select('
                    SUM(CAST(s.amount AS DECIMAL(20,2))) as total,
                    SUM(CASE WHEN CAST(s.amount AS DECIMAL(20,2)) < 0 THEN CAST(s.amount AS DECIMAL(20,2)) ELSE 0 END) as expense_amount,
                    SUM(CASE WHEN CAST(s.amount AS DECIMAL(20,2)) > 0 THEN CAST(s.amount AS DECIMAL(20,2)) ELSE 0 END) as income_amount,
                    COUNT(s.id) as entry_count
                ')
                ->from(Spent::class, 's')
                ->where('s.month = :month')
                ->andWhere('s.year = :year')
                ->setParameter('month', $month)
                ->setParameter('year', $year)
                ->getQuery()
                ->getSingleResult();

            return new JsonResponse([
                'total' => number_format((float) $result['total'], 2, '.', ''),
                'expense_amount' => number_format((float) $result['expense_amount'], 2, '.', ''),
                'income_amount' => number_format((float) $result['income_amount'], 2, '.', ''),
                'entry_count' => (int) $result['entry_count'],
                'filters' => [
                    'month' => $month,
                    'year' => $year
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch monthly breakdown'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/breakdown_year', name: 'breakdown_year_spent', methods: ['GET'])]
    public function breakdownYear(Request $request): JsonResponse
    {
        $authResponse = $this->authService->checkAuthentication($request);
        if ($authResponse) {
            return $authResponse;
        }

        $year = $request->query->get('year');

        if (!$year) {
            return new JsonResponse(['error' => 'Year parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $year = (int) $year;

        if ($year < 1900 || $year > 9999) {
            return new JsonResponse(['error' => 'Year must be between 1900 and 9999'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->entityManager->createQueryBuilder()
                ->select('
                    SUM(CAST(s.amount AS DECIMAL(20,2))) as total,
                    SUM(CASE WHEN CAST(s.amount AS DECIMAL(20,2)) < 0 THEN CAST(s.amount AS DECIMAL(20,2)) ELSE 0 END) as expense_amount,
                    SUM(CASE WHEN CAST(s.amount AS DECIMAL(20,2)) > 0 THEN CAST(s.amount AS DECIMAL(20,2)) ELSE 0 END) as income_amount,
                    COUNT(s.id) as entry_count
                ')
                ->from(Spent::class, 's')
                ->where('s.year = :year')
                ->setParameter('year', $year)
                ->getQuery()
                ->getSingleResult();

            return new JsonResponse([
                'total' => number_format((float) $result['total'], 2, '.', ''),
                'expense_amount' => number_format((float) $result['expense_amount'], 2, '.', ''),
                'income_amount' => number_format((float) $result['income_amount'], 2, '.', ''),
                'entry_count' => (int) $result['entry_count'],
                'filters' => [
                    'year' => $year
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch yearly breakdown'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/balance', name: 'balance_spent', methods: ['GET'])]
    public function balance(Request $request): JsonResponse
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
            $result = $this->entityManager->createQueryBuilder()
                ->select('
                    SUM(CAST(s.amount AS DECIMAL(20,2))) as balance,
                    COUNT(s.id) as entry_count
                ')
                ->from(Spent::class, 's')
                ->where('s.year < :year OR (s.year = :year AND s.month < :month)')
                ->setParameter('year', $year)
                ->setParameter('month', $month)
                ->getQuery()
                ->getSingleResult();

            return new JsonResponse([
                'balance' => number_format((float) $result['balance'], 2, '.', ''),
                'entry_count' => (int) $result['entry_count'],
                'filters' => [
                    'before_month' => $month,
                    'before_year' => $year
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch balance'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}