<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\Dto\Request\UserRegistrationDto;
use App\Dto\Response\CurrentUserDto;
use App\Entity\User;
use App\Service\PaymentService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserApiTest extends AbstractTest
{
    private $serializer;
    private string $apiPath = '/api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [new UserFixtures(
            self::getContainer()->get(UserPasswordHasherInterface::class),
            self::getContainer()->get(PaymentService::class),
            self::getContainer()->get(RefreshTokenGeneratorInterface::class),
            self::getContainer()->get(RefreshTokenManagerInterface::class)
        )];
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

    public function testGetCurrentUserWithAuth(): void
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

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/users/current',
            server: $headers,
        );

        $this->assertResponseOk();

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $userDto = $this->serializer->deserialize(
            $client->getResponse()->getContent(),
            CurrentUserDto::class,
            'json'
        );

        $userRepository = self::getEntityManager()->getRepository(User::class);
        $actualUser = $userRepository->findOneBy(['email' => $user['username']]);

        self::assertEquals($actualUser->getEmail(), $userDto->username);
        self::assertEquals($actualUser->getRoles(), $userDto->roles);
        self::assertEquals($actualUser->getBalance(), $userDto->balance);
    }

    public function testGetCurrentUserWithInvalidToken(): void
    {
        $token = 'qwerty123';

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/users/current',
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json);

        self::assertEquals('401', $json['code']);
        self::assertEquals('Invalid JWT Token', $json['message']);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/users/current',
            server: $headers,
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json);

        self::assertEquals('401', $json['code']);
        self::assertEquals('JWT Token not found', $json['message']);
    }
}
