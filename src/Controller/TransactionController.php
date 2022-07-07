<?php

namespace App\Controller;

use App\Dto\Response\Transfromer\TransactionResponseTransformer;
use App\Repository\TransactionRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/v1/transactions')]
class TransactionController extends AbstractController
{
    private const OPERATION_TYPE = [
        'payment' => 1,
        'deposit' => 2
    ];

    #[Route('/', name: 'app_transaction', methods: ['GET'])]
    public function index(Request $request, TransactionRepository $transactionRepository, SerializerInterface $serializer): Response
    {
        $filters = [];
        $filters['type'] = $request->query->get('type') ? self::OPERATION_TYPE[$request->query->get('type')] : null;
        $filters['course_code'] = $request->query->get('course_code');
        $filters['skip_expired'] = $request->query->get('skip_expired');

        $transactions = $transactionRepository->findUserTransactionsByFilters($this->getUser(), $filters);

        $transactionDto = TransactionResponseTransformer::transformFromObjects($transactions);

        $transactionResponse = $serializer->serialize($transactionDto, 'json');

        $response = new JsonResponse();
        $response->setContent($transactionResponse);
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
