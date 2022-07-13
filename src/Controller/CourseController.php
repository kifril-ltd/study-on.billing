<?php

namespace App\Controller;

use App\Dto\Request\CourseCreationRequestDto;
use App\Dto\Request\Transformer\CourseCreationTransformer;
use App\Dto\Response\PaymentDto;
use App\Dto\Response\Transfromer\CourseResponseTransformer;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('api/v1/courses')]
class CourseController extends AbstractController
{
    /**
     * @OA\Get(
     *     tags={"Courses"},
     *     path="/api/v1/courses/",
     *     summary="Список курсов",
     *     description="Список курсов",
     *     operationId="courses.index",
     * )
     */
    #[Route('/', name: 'app_course', methods: ['GET'])]
    public function index(CourseRepository $courseRepository, SerializerInterface $serializer): Response
    {
        $courses = $courseRepository->findAll();
        $coursesDto = CourseResponseTransformer::transformFromObjects($courses);

        $coursesResponse = $serializer->serialize($coursesDto, 'json');

        $response = new JsonResponse();
        $response->setContent($coursesResponse);

        return $response;
    }

    /**
     * @OA\Get(
     *     tags={"Courses"},
     *     path="/api/v1/courses/{code}",
     *     summary="Информация о курсе",
     *     description="Информация о курсе",
     *     operationId="courses.show",
     * )
     */
    #[Route('/{code}', name: 'app_course_show', methods: ['GET'])]
    public function show(string $code, CourseRepository $courseRepository, SerializerInterface $serializer): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        $response = new JsonResponse();

        $responseCode = '';
        $responseData = [];
        if ($course !== null) {
            $responseCode = Response::HTTP_OK;
            $responseData = CourseResponseTransformer::transformFromObject($course);
        } else {
            $responseCode = Response::HTTP_NOT_FOUND;
            $responseData = [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Курс ' . $code . ' не найден',
            ];
        }

        $responseData = $serializer->serialize($responseData, 'json');

        $response->setStatusCode($responseCode);
        $response->setContent($responseData);

        return $response;
    }

    /**
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/{code}/pay",
     *     summary="Оплата курса",
     *     description="Оплата курса",
     *     operationId="courses.pay",
     *     security={
     *         { "Bearer":{} },
     *     },
     * )
     */
    #[Route('/{code}/pay', name: 'app_course_pay', methods: ['POST'])]
    public function pay(
        string              $code,
        CourseRepository    $courseRepository,
        PaymentService      $paymentService,
        SerializerInterface $serializer
    ): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        $response = new JsonResponse();

        $responseCode = '';
        $responseData = [];
        if (!$course) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Курс не найден'
                ],
                Response::HTTP_NOT_FOUND
            );
        }


        /** @var User $user */
        $user = $this->getUser();
        try {
            $transaction = $paymentService->payment($user, $course);
        } catch (\Exception $exception) {
            throw new HttpException($exception->getCode(), $exception->getMessage());
        }

        $expiresAt = $transaction->getExpiresAt();

        $paymentDto = new PaymentDto();
        $paymentDto->status = true;
        $paymentDto->courseType = $course->getType();
        $paymentDto->expiresAt = $expiresAt ?: null;

        $responseData = $serializer->serialize($paymentDto, 'json');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($responseData);

        return $response;
    }

    /**
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/new",
     *     summary="Создание нового курса",
     *     description="Создание нового курса",
     *     operationId="courses.new",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @Model(type=CourseCreationRequestDto::class)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Курс успешно создан",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="true"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Курс с таким кодом уже существует",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="false"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Курс с таким кодом уже существует"
     *                 ),
     *             ),
     *        )
     *     ),
     * )
     */
    #[Route('/new', name: 'app_course_new', methods: ['POST'])]
    public function new(
        Request $request,
        SerializerInterface $serializer,
        CourseRepository $courseRepository,
        EntityManagerInterface $manager)
    {
        try {
            /** @var CourseCreationRequestDto $courseCreationRequest */
            $courseCreationRequest = $serializer->deserialize($request->getContent(), CourseCreationRequestDto::class, 'json');
            $course = $courseRepository->findOneBy(['code' => $courseCreationRequest->code]);

            if ($course) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'message' => 'Курс с таким кодом уже существует'
                    ],
                    Response::HTTP_CONFLICT
                );
            }

            $course = CourseCreationTransformer::transformToObject($courseCreationRequest);
            $manager->persist($course);
            $manager->flush();

            return new JsonResponse(
                [
                    'success' => true,
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Ошибка во время сохранения курса на стороне сервиса биллинга'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/{code}/edit",
     *     summary="Редактирование курса",
     *     description="Редактирование курса",
     *     operationId="courses.edit",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @Model(type=CourseCreationRequestDto::class)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Курс успешно изменен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="true"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Курс не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="false"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Курс с таким кодом не найден"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка во время изменения курса",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="false"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Ошибка во время изменения курса"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Курс с таким кодом уже существует",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="false"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Курс с таким кодом уже существует"
     *                 ),
     *             ),
     *        )
     *     ),
     * )
     */
    #[Route('/{code}/edit', name: 'app_course_edit', methods: ['POST'])]
    public function edit(
        string $code,
        Request $request,
        SerializerInterface $serializer,
        CourseRepository $courseRepository,
        EntityManagerInterface $manager)
    {
        try {
            /** @var CourseCreationRequestDto $courseEditRequest */
            $courseEditRequest = $serializer->deserialize($request->getContent(), CourseCreationRequestDto::class, 'json');

            $courseNewCode = $courseRepository->findOneBy(['code' => $courseEditRequest->code]);
            if ($courseEditRequest->code !== $code && $courseNewCode) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'message' => 'Курс с таким кодом уже существует'
                    ],
                    Response::HTTP_CONFLICT
                );
            }

            $course = $courseRepository->findOneBy(['code' => $code]);
            if (!$course) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'message' => 'Курс с таким кодом не найден'
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $course->updateFromDto($courseEditRequest);

            $manager->flush();

            return new JsonResponse(
                [
                    'success' => true,
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $exception) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Ошибка во время сохранения курса на стороне сервиса биллинга'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
}
