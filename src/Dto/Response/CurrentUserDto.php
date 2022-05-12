<?php

namespace App\Dto\Response;

use JMS\Serializer\Annotation as Serialization;

class CurrentUserDto
{
    #[Serialization\Type("string")]
    public string $username;

    #[Serialization\Type("array")]
    public array $roles;

    #[Serialization\Type("float")]
    public float $balance;
}