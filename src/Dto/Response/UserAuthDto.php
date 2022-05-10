<?php

namespace App\Dto\Response;

class UserAuthDto
{

    public string $username;

    public string $token;

    public array $roles;
}