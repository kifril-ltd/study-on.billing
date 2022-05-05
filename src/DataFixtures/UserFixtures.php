<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@study-on.local');

        $plainPassword = 'Qwerty123';
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPassword($hashedPassword);
        $manager->persist($user);

        $superUser = new User();
        $superUser->setEmail('admin@study-on.local');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $superUser,
            $plainPassword
        );
        $superUser->setPassword($hashedPassword);
        $superUser->setRoles(['ROLE_SUPER_ADMIN']);

        $manager->persist($superUser);

        $manager->flush();
    }
}
