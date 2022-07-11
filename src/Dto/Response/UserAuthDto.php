<?php

namespace App\Dto\Response;

use JMS\Serializer\Annotation as Serialization;

class UserAuthDto
{
    #[Serialization\Type('string')]
    public string $token;

    #[Serialization\Type('string')]
    public string $refreshToken;

    #[Serialization\Type('array<string>')]
    public array $roles;
}