<?php

namespace App\Controller;

use App\Dto\Request\Transformer\UserRegistrationRequestDtoTransformer;
use App\Dto\Request\UserRegistrationDto;
use App\Dto\Response\Transfromer\ErrorTransformer;
use App\Dto\Response\Transfromer\UserAuthResponseTransformer;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('api/v1')]
class ApiAuthController extends AbstractController
{
    private $serializer;
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
    }

    #[Route('/auth', name: 'api_login', methods: ['POST'])]
    public function login(): Response
    {
        //auth
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function registration(
        Request $request,
        UserRegistrationRequestDtoTransformer $registrationRequestDtoTransformer,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $tokenManager
    ): Response {
        $userDto = $this->serializer->deserialize(
            $request->getContent(),
            UserRegistrationDto::class,
            'json'
        );

        $errors = $this->validator->validate($userDto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (new ErrorTransformer())->transformErrorsToArray($errors),
            ], Response::HTTP_BAD_REQUEST);
        }
        if ($userRepository->findOneBy(['email' => $userDto->username])) {
            return $this->json([
                'error' => 'User already exists.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $registrationRequestDtoTransformer->transformToObject($userDto);
        $entityManager->persist($user);
        $entityManager->flush();

        $authDto = (new UserAuthResponseTransformer())->transformFromObject($user);
        $authDto->token = $tokenManager->create($user);

        return $this->json([
            $authDto,
        ], Response::HTTP_CREATED);
    }
}
