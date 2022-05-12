<?php

namespace App\Dto\Response;

use OpenApi\Annotations as OA;

class UserAuthDto
{
    /**
     * @OA\Property(type="string", title="token")
     */
    public string $token;

    public array $roles;
}