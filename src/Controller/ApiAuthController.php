<?php

namespace App\Controller;

use App\Dto\Request\Transformer\UserRegistrationRequestDtoTransformer;
use App\Dto\Request\UserRegistrationDto;
use App\Dto\Response\Transfromer\ErrorTransformer;
use App\Dto\Response\Transfromer\UserAuthResponseTransformer;
use App\Dto\Response\UserAuthDto;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolation;
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
     *          property="refresh_token",
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
        Request $request,
        UserRegistrationRequestDtoTransformer $registrationRequestDtoTransformer,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $tokenManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        SerializerInterface $serializer
    ): Response {
        $userDto = $this->serializer->deserialize(
            $request->getContent(),
            UserRegistrationDto::class,
            'json'
        );

        $errors = $this->validator->validate($userDto);
        if ($userRepository->findOneBy(['email' => $userDto->username])) {
            $errors->add(new ConstraintViolation(
                message: 'User ' . $userDto->username .  ' already exists.',
                messageTemplate: 'User {{ value }} already exists.',
                parameters: ['value' => $userDto->username],
                root: $userDto,
                propertyPath: 'username',
                invalidValue: $userDto->username
            ));
        }
        if (count($errors) > 0) {
            return $this->json([
                'errors' => (new ErrorTransformer())->transformErrorsToArray($errors),
            ], Response::HTTP_BAD_REQUEST);
        }


        $user = $registrationRequestDtoTransformer->transformToObject($userDto);
        $user->setBalance(0);
        $entityManager->persist($user);
        $entityManager->flush();

        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, (new \DateTime())->modify('+1 month')->getTimestamp());
        $refreshTokenManager->save($refreshToken);

        $authDto = (new UserAuthResponseTransformer())->transformFromObject($user);
        $authDto->token = $tokenManager->create($user);
        $authDto->refreshToken = $refreshToken->getRefreshToken();

        $response = new JsonResponse();
        $response->setContent($serializer->serialize($authDto, 'json'));
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
