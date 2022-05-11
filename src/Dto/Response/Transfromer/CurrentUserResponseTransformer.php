<?php

namespace App\Dto\Response\Transfromer;

use App\Dto\Response\CurrentUserDto;
use App\Dto\Response\UserAuthDto;
use App\Entity\User;

class CurrentUserResponseTransformer
{
    public function transformFromObject(User $user): CurrentUserDto
    {
        $currentUserDto = new CurrentUserDto();

        $currentUserDto->username = $user->getEmail();
        $currentUserDto->roles = $user->getRoles();
        $currentUserDto->balance = $user->getBalance();

        return $currentUserDto;
    }
}