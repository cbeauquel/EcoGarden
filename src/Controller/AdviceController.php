<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\MonthRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class AdviceController extends AbstractController
{
    #[Route('/api/conseils', name: 'advice', methods:['GET'])]
    public function getAdviceList(MonthRepository $monthRepository, SerializerInterface $serializer): JsonResponse
    {
        $nbMonth = (new \DateTime())->format('m');
        $adviceList = $monthRepository->findByMonthNumber($nbMonth);
        $jsonAdviceList = $serializer->serialize($adviceList, 'json', ['groups' => 'Advice:List']);
        return new JsonResponse(
            $jsonAdviceList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/conseils/{nbMonth}', name: 'monthAdvice', methods:['GET'], requirements:['id' => '\d+'])]
    public function getAdviceDetail(MonthRepository $monthRepository, SerializerInterface $serializer, int $nbMonth): JsonResponse
    {
        $adviceMonth = $monthRepository->findByMonthNumber($nbMonth);
        $jsonAdviceDetail = $serializer->serialize($adviceMonth, 'json', ['groups' => 'Advice:Read']);
        return new JsonResponse(
            $jsonAdviceDetail, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour afficher la liste des utilisateurs')]
    #[Route('/api/conseils/{id}', name: 'deleteAdvice', methods: ['DELETE'])]
    public function deleteAdvice(Advice $advice, EntityManagerInterface $manager): JsonResponse 
    {
        $manager->remove($advice);
        $manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour afficher la liste des utilisateurs')]
    #[Route('/api/conseil', name:"createAdvice", methods: ['POST'])]
    public function createAdvice(
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $manager, 
        UrlGeneratorInterface $urlGenerator,
        MonthRepository $monthRepository,
        ): JsonResponse 
    {

        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();

        // Récupération de l'idMonth. S'il n'est pas défini, alors on met -1 par défaut.
        $nbMonth = $content['nbMonth'] ?? -1;

        // On cherche le mois qui correspond et on l'assigne au conseil.
        // Si "find" ne trouve pas le mois, alors null sera retourné.
        $advice->setMonth($monthRepository->findBy($nbMonth));

        $manager->persist($advice);
        $manager->flush();

        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'Advice:Read']);
        
        $location = $urlGenerator->generate('detailAdvice', ['id' => $advice->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAdvice, Response::HTTP_CREATED, ["Location" => $location], true);
   }

   #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour afficher la liste des utilisateurs')]
   #[Route('/api/conseils/{id}', name:"updateAdvice", methods:['PUT'])]
   public function updateAdvice(
        Request $request, 
        SerializerInterface $serializer, 
        Advice $currentAdvice, 
        EntityManagerInterface $manager, 
        MonthRepository $monthRepository
        ): JsonResponse 
   {
       $updatedAdvice = $serializer->deserialize($request->getContent(), 
               Advice::class, 
               'json', 
               [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);

       $content = $request->toArray();
       $nbMonth = $content['nbMonth'] ?? -1;

       $updatedAdvice->setMonth($monthRepository->findBy($nbMonth));
       
       $manager->persist($updatedAdvice);
       $manager->flush();
       return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }

}
