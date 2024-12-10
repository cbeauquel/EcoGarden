<?php

namespace App\Controller;

use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[IsGranted('ROLE_ADMIN', message:'Vous devez être administrateur pour afficher la liste des utilisateurs')]
    #[Route('/api/users', name: 'user', methods:['GET'])]
    public function getUserList(UserRepository $user, SerializerInterface $serializer): JsonResponse
    {

        $userList = $user->findAll();
        $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'User:Read', 'User:List']);
        return new JsonResponse(
            $jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user', name: 'User', methods:['GET'])]
    public function getUserDetail(SerializerInterface $serializer): JsonResponse
    {
        $currentUser = $this->security->getUser();
        $jsonUserDetail = $serializer->serialize($currentUser, 'json', ['groups' => 'User:Read']);
        return new JsonResponse(
            $jsonUserDetail, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/user/{id}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $manager): JsonResponse 
    {
        $manager->remove($user);
        $manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/user', name:"createUser", methods: ['POST'])]
    public function createUser(
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $manager, 
        UrlGeneratorInterface $urlGenerator,
        UserPasswordHasherInterface $userPasswordHasher,
        ): JsonResponse 
    {

        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $content = $request->toArray();
        /** @var string $plainPassword */
        $plainPassword = $content['password'];

        // encode the plain password
        $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

        $manager->persist($user);
        $manager->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'User:Read']);
        
        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
   }

   #[Route('/api/user/{id}', name:"updateUser", methods:['PUT'])]
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
       
        $content = $request->toArray();
        /** @var string $plainPassword */
        $plainPassword = $content['password'];

        // encode the plain password
        $updatedUser->setPassword($userPasswordHasher->hashPassword($updatedUser, $plainPassword));


       $manager->persist($updatedUser);
       $manager->flush();
       return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }

}
