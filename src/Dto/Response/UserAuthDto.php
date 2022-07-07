<?php

namespace App\Dto\Response;

use JMS\Serializer\Annotation as Serialization;

class UserAuthDto
{
    /**
     * @OA\Property(type="string", title="token")
     */
    public string $token;

    public array $roles;
}