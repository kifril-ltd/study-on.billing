<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;

class AuthApiTest extends AbstractTest
{
    private $serializer;
    private string $apiPath = '/api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [UserFixtures::class];
    }

    public function testAuthWithExistingUser(): void
    {
        $user = [
            'username' => 'user@study-on.local',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/auth',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseOk();

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    public function testAuthWithNotExistingUser(): void
    {
        $user = [
            'username' => 'test@study-on.local',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/auth',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['code']);
        self::assertNotEmpty($json['message']);

        self::assertEquals('401', $json['code']);
        self::assertEquals('Invalid credentials.', $json['message']);
    }

    public function testRegistrationSuccessful(): void
    {
        $user = [
            'username' => 'test@study-on.local',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CREATED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
        self::assertNotEmpty($json['roles']);

        self::assertContains('ROLE_USER', $json['roles']);
    }

    public function testRegistrationValidationErrors(): void
    {
        $user = [
            'username' => 'teststudy-on.local',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath . '/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['username']);

        self::assertContains("The email \"teststudy-on.local\" is not a valid email.", $json['errors']['username']);

        // Проверка валидации длины пароля
        $user = [
            'username' => 'test@study-on.local',
            'password' => '123'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath . '/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['password']);

        self::assertContains("The password must be at least 6 characters.", $json['errors']['password']);

        // Проверка валидации полей на пустоту
        $user = [
            'username' => '',
            'password' => ''
        ];

        $client->request(
            'POST',
            $this->apiPath . '/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['password']);
        self::assertNotEmpty($json['errors']['username']);

        self::assertContains("The password must be at least 6 characters.", $json['errors']['password']);
        self::assertContains("The password field can't be blank.", $json['errors']['password']);
        self::assertContains("The username field can't be blank.", $json['errors']['username']);

        // Проверка валидации поля email на корректность
        $user = [
            'username' => 'email',
            'password' => 'Qwerty123'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath . '/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['username']);

        self::assertContains("The email \"email\" is not a valid email.", $json['errors']['username']);

        // Проверка валидации при существующем пользователе
        $user = [
            'username' => 'user@study-on.local',
            'password' => 'Qwerty123'
        ];

        $client->request(
            'POST',
            $this->apiPath . '/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['username']);

        self::assertContains("User user@study-on.local already exists.", $json['errors']['username']);
    }
}
