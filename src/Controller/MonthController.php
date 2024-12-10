<?php

namespace App\Controller;

use App\Entity\Month;
use App\Repository\MonthRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MonthController extends AbstractController
{
    #[Route('/api/months', name: 'month', methods:['GET'])]
    public function getMonthList(MonthRepository $month, SerializerInterface $serializer): JsonResponse
    {
        $monthList = $month->findAll();
        $jsonMonthList = $serializer->serialize($monthList, 'json', ['groups' => 'Month:Read']);
        return new JsonResponse(
            $jsonMonthList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/months/{id}', name: 'detailMonth', methods:['GET'], requirements:['id' => '\d+'])]
    public function getMonthDetail(Month $monthDetail, SerializerInterface $serializer): JsonResponse
    {
        $jsonMonthDetail = $serializer->serialize($monthDetail, 'json', ['groups' => 'Month:Read']);
        return new JsonResponse(
            $jsonMonthDetail, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
