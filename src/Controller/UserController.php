<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private UserPasswordHasherInterface $userPasswordHasher;
    private ValidatorInterface $validator;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, ValidatorInterface $validator)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->validator = $validator;
    }

    #[Route('/api/user', name: 'api_user_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = new User();
        $data = json_decode($request->getContent(), true);

        // Vérifiez si les données requises sont présentes
        if (!isset($data['email']) || !isset($data['plainPassword'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        // Remplir les champs de l'utilisateur
        $user->setEmail($data['email']);
        $user->setPlainPassword($data['plainPassword']);

        // Validation des données
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Hacher le mot de passe
        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashedPassword);

        // Persister l'utilisateur dans la base de données
        try {
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'User could not be created: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'User created successfully',
            'user' => [
                'email' => $user->getEmail(),
                // Vous pouvez ajouter d'autres champs ici si nécessaire
            ]
        ], Response::HTTP_CREATED);
    }
}
