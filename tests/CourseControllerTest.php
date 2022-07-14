<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\UserFixtures;
use App\Dto\Response\CourseDto;
use App\Dto\Response\PaymentDto;
use App\Service\PaymentService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CourseControllerTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    private string $apiPath = '/api/v1/courses';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [
            new UserFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(PaymentService::class),
                self::getContainer()->get(RefreshTokenGeneratorInterface::class),
                self::getContainer()->get(RefreshTokenManagerInterface::class)
            ),
            new CourseFixtures()
        ];
    }

    private function getToken($user)
    {
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testGetAllCourses()
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/',
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertCount(8, $response);
    }

    public function testGetCourseByCodeAuthorizedUser()
    {
        $user = [
            'username' => 'user@study-on.local',
            'password' => 'Qwerty123'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $existingCourseCode = 'PPBI';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/' . $existingCourseCode,
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var CourseDto $courseDto */
        $courseDto = $this->serializer->deserialize($client->getResponse()->getContent(), CourseDto::class, 'json');

        self::assertNotNull($courseDto, 'Курс не найден');
        self::assertEquals('PPBI', $courseDto->code);
        self::assertEquals('rent', $courseDto->type);
        self::assertEquals(2000, $courseDto->price);

        $notExistingCourseCode = '123';
        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/' . $notExistingCourseCode,
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_NOT_FOUND, $client->getResponse());
    }

    public function testPayCourseAuthorizedUser()
    {
        $user = [
            'username' => 'user@study-on.local',
            'password' => 'Qwerty123'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCode = 'PPBI';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/' . $courseCode . '/pay',
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var PaymentDto $paymentDto */
        $paymentDto = $this->serializer->deserialize($client->getResponse()->getContent(), PaymentDto::class, 'json');

        self::assertEquals(true, $paymentDto->status);

        $courseCode = 'CAMPB';
        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/' . $courseCode . '/pay',
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_NOT_ACCEPTABLE, $client->getResponse());
    }

    public function testPayCourseUnauthorizedUser()
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $courseCode = 'PPBI';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/' . $courseCode . '/pay',
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }
}
