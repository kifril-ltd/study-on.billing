<?php

namespace App\Controller;

use App\Dto\Request\Transformer\UserRegistrationRequestDtoTransformer;
use App\Dto\Request\UserRegistrationDto;
use App\Dto\Response\Transfromer\ErrorTransformer;
use App\Dto\Response\Transfromer\UserAuthResponseTransformer;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;

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

    /**
     * @OA\Post(
     *     path="api/v1/auth",
     *     description="Authenticate user with JWT token",
     * )
     * @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *          @OA\Property(
     *              property="username",
     *              type="string",
     *              example="user@example.com"
     *          ),
     *          @OA\Property(
     *              property="password",
     *              type="string",
     *              example="Qwerty123"
     *          )
     *       )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns JWT token for user authentication",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string"
     *        )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="User authentication failed",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Unxepected error",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     */
    #[Route('/auth', name: 'api_login', methods: ['POST'])]
    public function login(): Response
    {
        //auth
    }

    /**
     * @OA\Post(
     *     path="api/v1/register",
     *     description="Register new user",
     * )
     * @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *          @OA\Property(
     *              property="username",
     *              type="string",
     *              example="user@example.com"
     *          ),
     *          @OA\Property(
     *              property="password",
     *              type="string",
     *              example="Qwerty123"
     *          )
     *       )
     * )
     * @OA\Response(
     *     response=201,
     *     description="User creation success",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="roles",
     *          type="array",
     *          @OA\Items(type="string")
     *        )
     *
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Validation fail",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="errors",
     *          type="array",
     *          @OA\Items(
     *              @OA\Property(
     *                  type="string",
     *                  property="property_name"
     *              )
     *          )
     *        )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="User authentication failed",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Unxepected error",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function registration(
        Request                               $request,
        UserRegistrationRequestDtoTransformer $registrationRequestDtoTransformer,
        UserRepository                        $userRepository,
        EntityManagerInterface                $entityManager,
        JWTTokenManagerInterface              $tokenManager
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
