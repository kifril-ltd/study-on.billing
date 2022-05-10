<?php

namespace App\Dto\Response\Transfromer;

use App\Dto\Response\UserAuthDto;
use App\Entity\User;

class UserAuthResponseTransformer
{
    public function transformFromObject(User $user): UserAuthDto
    {
        $authDto = new UserAuthDto();
        $authDto->username = $user->getUsername();
        $authDto->roles = $user->getRoles();

        return $authDto;
    }
}