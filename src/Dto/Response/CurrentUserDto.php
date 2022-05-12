<?php

namespace App\Dto\Response;

class CurrentUserDto
{
    public string $username;

    public array $roles;

    public float $balance;
}