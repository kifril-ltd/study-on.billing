<?php

namespace App\Controller;

use App\Dto\Response\PaymentDto;
use App\Dto\Response\Transfromer\CourseResponseTransformer;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/v1/courses')]
class CourseController extends AbstractController
{
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
            $dataResponse = [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Данный курс не найден',
            ];
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
}
