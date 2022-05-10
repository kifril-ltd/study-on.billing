<?php

namespace App\Dto\Request\Transformer;

use App\Dto\Request\UserRegistrationDto;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationRequestDtoTransformer implements RequestDtoTransformerInterface
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function transformToObject($userDto)
    {
        $user = new User();
        $user->setEmail($userDto->username);

        $plainPassword = $userDto->password;
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPassword($hashedPassword);

        return $user;
    }
}