<?php

namespace App\Controller;

use App\Entity\Spent;
use App\Repository\SpentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/api/spent')]
class SpentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SpentRepository $spentRepository;
    private ParameterBagInterface $parameterBag;

    public function __construct(EntityManagerInterface $entityManager, SpentRepository $spentRepository, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->spentRepository = $spentRepository;
        $this->parameterBag = $parameterBag;
    }

    #[Route('', name: 'create_spent', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Check API token authentication
        $providedToken = $request->headers->get('Authorization');
        $expectedToken = $this->parameterBag->get('app.api_token');
        
        if (!$providedToken || !str_starts_with($providedToken, 'Bearer ')) {
            return new JsonResponse(['error' => 'Authorization header required'], Response::HTTP_UNAUTHORIZED);
        }
        
        $token = substr($providedToken, 7); // Remove 'Bearer ' prefix
        
        if ($token !== $expectedToken) {
            return new JsonResponse(['error' => 'Invalid API token'], Response::HTTP_UNAUTHORIZED);
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
                $spent->setMonth((int) $date->format('n'));
                $spent->setYear((int) $date->format('Y'));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $now = new \DateTime();
            $spent->setDate($now);
            $spent->setMonth((int) $now->format('n'));
            $spent->setYear((int) $now->format('Y'));
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
}