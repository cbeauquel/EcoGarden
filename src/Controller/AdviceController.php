<?php

namespace App\Controller;

use App\Entity\Advice;
use OpenApi\Attributes as OA;
use App\Repository\MonthRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class AdviceController extends AbstractController
{
    #[Route('/api/conseil', name: 'advice', methods:['GET'])]    
    /**
     * getAdviceList : récupération des conseils (advices) du mois en cours
     *
     * @param  mixed $monthRepository
     * @param  mixed $serializer
     * @param  mixed $cachePool
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description:'Retourne la liste des conseils pour le mois en cours',
        content: new OA\JsonContent(
           type:'array',
           items: new OA\Items(ref: new Model(type:Advice::class, groups: ['Advice:List']))
        )
    )]
    #[OA\Tag(name:'Conseils')]
    public function getAdviceList(MonthRepository $monthRepository, SerializerInterface $serializer, TagAwareCacheInterface $cachePool): JsonResponse
    {
        //on initialise le cache
        $cacheKey = "getAdviceList";
        
        //on récupère l'item dans le cache, à l'aide de la clé (cacheKey)
        $adviceList = $cachePool->get($cacheKey, function ($cacheItem) use ($monthRepository) {
            $cacheItem->expiresAfter(1800); // Expire après 30 minutes
            $nbMonth = (new \DateTime())->format('m');

            return $monthRepository->findByMonthNumber($nbMonth);
        });

        //sérialisation du résultat
        $jsonAdviceList = $serializer->serialize($adviceList, 'json', ['groups' => 'Advice:List']);
        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/conseil/{nbMonth}', name: 'monthAdvice', methods:['GET'], requirements:['nbMonth' => '\d+'])]
    #[OA\Response(
        response:200,
        description:'Retourne la liste des conseils pour le mois voulu',
        content: new OA\JsonContent(
           type:'array',
           items: new OA\Items(ref: new Model(type:Advice::class, groups: ['Advice:Read']))
        )
    )]
    #[OA\Tag(name:'Conseils')]
    /**
     * getAdviceByMonth : affiche les conseils d'un mois donné dans la requête
     *
     * @param  mixed $monthRepository
     * @param  mixed $serializer
     * @param  mixed $nbMonth
     * @param  mixed $cachePool
     * @param  mixed $request
     * @return JsonResponse
     */
    public function getAdviceByMonth(
        MonthRepository $monthRepository, 
        SerializerInterface $serializer, 
        int $nbMonth,
        ): JsonResponse
    {
        //si le numéro de mois saisi n'est pas un mois valide
        if ($nbMonth < 1 || $nbMonth > 12) {
            return new JsonResponse([
                'error' => "Le numéro du mois doit être compris entre 1 et 12."
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        //récupération de la liste des advices en fonction du numéro du mois

        $adviceMonth = $monthRepository->findByMonthNumber($nbMonth);

        $jsonAdviceDetail = $serializer->serialize($adviceMonth, 'json', ['groups' => 'Advice:Read']);

        return new JsonResponse($jsonAdviceDetail, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour agir sur les conseils')]
    #[Route('/api/conseil-d/{id}', name: 'detailAdvice', methods:['GET'], requirements:['id' => '\d+'])]
    #[OA\Response(
        response:200,
        description:'Retourne la liste des conseils pour l\'identifiant voulu',
        content: new OA\JsonContent(
           type:'array',
           items: new OA\Items(ref: new Model(type:Advice::class, groups: ['Advice:Read']))
        )
    )]
    #[OA\Tag(name:'Admin')]
    /**
     * getAdviceByMonth : affiche les conseils d'un mois donné dans la requête
     *
     * @param  mixed $monthRepository
     * @param  mixed $serializer
     * @param  mixed $nbMonth
     * @param  mixed $cachePool
     * @param  mixed $request
     * @return JsonResponse
     */
    public function getAdviceById(
        MonthRepository $monthRepository, 
        SerializerInterface $serializer, 
        ?Advice $advice
        ): JsonResponse
    {
        //récupération de la liste des advices en fonction de l'identifiant saisi

        $jsonAdviceDetail = $serializer->serialize($advice, 'json', ['groups' => 'Advice:Read']);

        return new JsonResponse($jsonAdviceDetail, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour agir sur les conseils')]
    #[Route('/api/conseil/{id}', name: 'deleteAdvice', methods: ['DELETE'])]    
    #[OA\Tag(name:'Admin')]
    /**
     * deleteAdvice : suppression d'un conseil (admin seuelement)
     *
     * @param  mixed $advice
     * @param  mixed $manager
     * @return JsonResponse
     */
    public function deleteAdvice(Advice $advice, EntityManagerInterface $manager): JsonResponse 
    {
        $manager->remove($advice);
        $manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour agir sur les conseils')]
    #[Route('/api/conseil', name:"createAdvice", methods: ['POST'])] 
    #[OA\RequestBody(
        required: true,
        content:new OA\JsonContent(ref: new Model(type: Advice::class))
    )]
    #[OA\Tag(name:'Admin')]
    /**
     * postAdvice : création d'un conseil et attribution d'un ou plusieurs mois (manyTomany)
     *
     * @return void
     */
    public function postAdvice(
        EntityManagerInterface $manager, 
        SerializerInterface $serializer, 
        Request $request, 
        MonthRepository $monthRepository,
        ValidatorInterface $validator,
        ): JsonResponse
    {
        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');
        
        //on vérifie les erreurs
        $errors = $validator->validate($advice);

        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();

        //On utilise la méthode setContent pour le conseil que l'on souhaite créer
        $advice->setContent($content['content']);

        $advice->getMonths()->clear(); // Efface les anciennes relations

        //Les numéros de mois sont saisis dans un tableau, la boucle for each permet d'attribuer les mois au conseil créé
        foreach ($content['months'] as $number) {
            //on retrouve l'objet mois qui correspond à sa propriété number
            $month = $monthRepository->findIdBy(['number' => $number]);

            if (!$month) {
                // on renvoie une erreur si un mois n'est pas trouvé
                return new JsonResponse([
                    'error' => "Le mois avec le numéro {$number} est introuvable, il n'y a que 12 mois dans l'année ;-)."
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $advice->addMonth($month);
        }
        $manager->persist($advice);
        $manager->flush();

        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'getAdvices']);

        return new JsonResponse($jsonAdvice, JsonResponse::HTTP_CREATED, ['message' => 'Conseil mis à jour avec succès.'], true);
   }

   #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour agir sur les conseils')]
   #[Route('/api/conseil/{id}', name:"updateAdvice", methods:['PUT'], requirements:['id' => '\d+'])]  
   #[OA\RequestBody(
    required: true,
    content:new OA\JsonContent(ref: new Model(type: Advice::class))
    )]
   #[OA\Tag(name:'Admin')]
   /**
    * updateAdvice : mise à jour du conseil (admin seulement)
    *
    * @return void
    */
   public function updateAdvice(
        Request $request, 
        SerializerInterface $serializer, 
        Advice $currentAdvice, 
        EntityManagerInterface $manager, 
        MonthRepository $monthRepository,
        ValidatorInterface $validator
        ): JsonResponse 
   {
       $updatedAdvice = $serializer->deserialize($request->getContent(), 
               Advice::class, 
               'json', 
               [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);

        //on vérifie les erreurs
        $errors = $validator->validate($updatedAdvice);

        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
       
       $content = $request->toArray();
       
        //Si la clé "months" est trouvée dans le tableau de la requête, on applique la mise à jour
       if(isset($content['months']))
       {
            //on efface les anciennes relations 
            $updatedAdvice->getMonths()->clear(); // Efface les anciennes relations

            foreach ($content['months'] as $number) {
                $month = $monthRepository->findIdBy(['number' => $number]);

                if (!$month) {
                    // on renvoie une erreur si un mois n'est pas trouvé
                    return new JsonResponse([
                        'error' => "Le mois avec le numéro {$number} est introuvable, il n'y a que 12 mois dans l'année ;-)."
                    ], JsonResponse::HTTP_NOT_FOUND);
                }

            $updatedAdvice->addMonth($month);
            }
        }

       $manager->persist($updatedAdvice);
       $manager->flush();

       return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }

}
