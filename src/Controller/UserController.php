<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use DateTimeImmutable;
use App\Entity\Company;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    // TODO - Authentication, Richardson's levels & Exception ?

    #[Route('/users/company/{id}', name: 'show_all_users', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showAll(UserRepository $userRepository, ?Company $company, SerializerInterface $serializer): JsonResponse
    {
        if(!$company){
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        $users = $userRepository->findBy(['company' => $company]);

        $jsonUsers = $serializer->serialize($users, 'json', ['groups' => 'showUsers']);

        return new JsonResponse($jsonUsers, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/users/{userId}/company/{id}', name: 'show_one_user', methods: ['GET'], requirements: ['userId' => '\d+', 'id' => '\d+'])]
    public function showOne(?Company $company, int $userId, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        if(!$company){
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $userRepository->find($userId);

        if(!$user){
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        if($company !== $user->getCompany()){
            return new JsonResponse(null, JsonResponse::HTTP_UNAUTHORIZED); // or forbidden ?
        }

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'showUsers']);

        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/users/{userId}/company/{id}', name: 'delete_user', methods: ['DELETE'], requirements: ['userId' => '\d+', 'id' => '\d+'])]
    public function delete(?Company $company, int $userId, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        if(!$company){
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $userRepository->find($userId);

        if(!$user){
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        if($company !== $user->getCompany()){
            return new JsonResponse(null, JsonResponse::HTTP_UNAUTHORIZED); // or forbidden ?
        }

        $company->removeUser($user);
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


    #[Route('/users/company/{id}', name: 'create_user', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function create(Request $request, ?Company $company, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        if(!$company){
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $user->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()))
            ->setCompany($company)
        ;

        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'showUsers']);

        return new JsonResponse($jsonUser, JsonResponse::HTTP_CREATED, [], true);
    }
}
