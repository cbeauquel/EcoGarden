<?php

namespace App\Controller;

use App\Entity\User;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserController extends AbstractController
{
    #[Route('/api/users', name: 'user', methods:['GET'])]    
    /**
     * getUserList Liste paginée des utilisateurs (administrateurs seulement)
     *
     * @param  mixed $user
     * @param  mixed $serializer
     * @param  mixed $request
     * @return JsonResponse
     */
    #[OA\Parameter(
        name:'page',
        in:'query',
        description:'La page que l\'on veut récupérer',
        schema: new OA\Schema(type:'int')
    )]
    #[OA\Parameter(
        name:'limit',
        in:'query',
        description:'Le nombre d\'éléments que l\'on veut récupérer',
        schema: new OA\Schema(type:'int')
    )]
    #[OA\Response(
        response:200,
        description:'Retourne la liste des utilisateurs',
        content: new OA\JsonContent(
           type:'array',
           items: new OA\Items(ref: new Model(type:User::class, groups: ['User:Read']))
        )
    )]
    #[OA\Tag(name:'Users')]
    public function getUserList(UserRepository $user, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $userList = $user->findAllWithPagination($page, $limit);
        $canListAll = $this->isGranted('ROLE_ADMIN');
        if($canListAll){
            $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'User:List']);
        } else {
            $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'User:Read']);
        }

        return new JsonResponse(
            $jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user', name: 'UserMe', methods:['GET'])]    
    /**
     * getUserDetail : affiche les informations de l'utilisateur connecté
     *
     * @param  mixed $serializer
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description:'Retourne les infos de l\'utilisateur connecté',
        content: new OA\JsonContent(
           type:'array',
           items: new OA\Items(ref: new Model(type:User::class, groups: ['User:Read']))
        )
    )]
    #[OA\Tag(name:'Users')]

    public function getUserMe(SerializerInterface $serializer): JsonResponse
    {
        $currentUser = $this->getUser();
        $jsonUserDetail = $serializer->serialize($currentUser, 'json', ['groups' => 'User:List']);
        return new JsonResponse(
            $jsonUserDetail, Response::HTTP_OK, ['accept' => 'json'], true);
    }
    
    #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour agir sur les utilisateurs')]
    #[Route('/api/user/{id}', name: 'User', methods:['GET'], requirements:['id' => '\d+'])]    
    /**
     * getUserDetail : affiche les informations de l'utilisateur voulu (admin seulement)
     *
     * @param  mixed $serializer
     * @return JsonResponse
     */
    #[OA\Response(
       response:200,
       description:'Retourne les infos de l\'utilisateur voulu',
       content: new OA\JsonContent(
          type:'array',
          items: new OA\Items(ref: new Model(type:User::class, groups: ['User:List']))
       )
    )]
    #[OA\Tag(name:'Admin')]

    public function getUserDetail(SerializerInterface $serializer, ?User $user): JsonResponse
    {
        $jsonUserDetail = $serializer->serialize($user, 'json', ['groups' => 'User:List']);
        return new JsonResponse(
            $jsonUserDetail, Response::HTTP_OK, ['accept' => 'json'], true);
    }


    #[Route('/api/user', name: 'deleteMe', methods: ['DELETE'])]    
    /**
     * deleteMe : Permet à l'utilisateur de supprimer son compte
     *
     * @param  mixed $manager
     * @return JsonResponse
     */
    #[OA\Tag(name:'Users')]
    public function deleteMe(EntityManagerInterface $manager): JsonResponse 
    {
        $currentUser = $this->getUser();

        $manager->remove($currentUser);
        $manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/user', name:"createUser", methods: ['POST'])]    
    /**
     * createUser : permet à l'utilisateur de créer son compte
     *
     * @return void
     */
    #[OA\RequestBody(
        required: true,
        content:new OA\JsonContent(ref: new Model(type: User::class))
    )]
    #[OA\Tag(name:'Users')]

    public function createUser(
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $manager, 
        UrlGeneratorInterface $urlGenerator,
        UserPasswordHasherInterface $userPasswordHasher,
        ValidatorInterface $validator,
        ): JsonResponse 
    {

        $user = $serializer->deserialize($request->getContent(), User::class, 'json', ['groups' => 'User:Write']);
        //on vérifie les erreurs
        $errors = $validator->validate($user);

        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $content = $request->toArray();
        /** @var string $plainPassword */
        $plainPassword = $content['password'];

        // encode the plain password
        $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

        $manager->persist($user);
        $manager->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'User:Read']);
        
        $location = $urlGenerator->generate('user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
   }

   #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour agir sur les utilisateurs')]
   #[Route('/api/user/{id}', name: 'deleteUser', methods: ['DELETE'], requirements:['id' => '\d+'])]   
   /**
    * deleteUser : SUpprime le compte de l'utilisateur voulu (admin seulement)
    *
    * @param  mixed $user
    * @param  mixed $manager
    * @return JsonResponse
    */
    #[OA\Tag(name:'Admin')]

   public function deleteUser(User $user, EntityManagerInterface $manager): JsonResponse 
   {

       $manager->remove($user);
       $manager->flush();

       return new JsonResponse(null, Response::HTTP_NO_CONTENT);
   }

   #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour agir sur les utilisateurs')]
   #[Route('/api/user/{id}', name:"updateUser", methods:['PUT'], requirements:['id' => '\d+'])]   
   /**
    * updateUser Modifie les informations de l'utilisateur (admin seulement)
    *
    * @return void
    */
    #[OA\RequestBody(
        required: true,
        content:new OA\JsonContent(ref: new Model(type: User::class))
    )]
    #[OA\Tag(name:'Admin')]
   public function updateUser(
        Request $request, 
        SerializerInterface $serializer, 
        User $currentUser, 
        EntityManagerInterface $manager, 
        UserPasswordHasherInterface $userPasswordHasher,
        ): JsonResponse 
   {
       $updatedUser = $serializer->deserialize($request->getContent(), 
               User::class, 
               'json', 
               [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);
       
       $manager->persist($updatedUser);
       $manager->flush();
       return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }

}