<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenManager;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;
    private $paymentService;
    private $refreshTokenGenerator;
    private $refreshTokenManager;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        PaymentService $paymentService,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager) {
        $this->passwordHasher = $passwordHasher;
        $this->paymentService = $paymentService;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
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
        $user->setBalance(0);
        $manager->persist($user);
        $this->paymentService->deposit($user, $_ENV['START_AMOUNT']);

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, (new \DateTime())->modify('+1 month')->getTimestamp());
        $this->refreshTokenManager->save($refreshToken);

        $superUser = new User();
        $superUser->setEmail('admin@study-on.local');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $superUser,
            $plainPassword
        );
        $superUser->setPassword($hashedPassword);
        $superUser->setRoles(['ROLE_SUPER_ADMIN']);
        $superUser->setBalance(0);
        $manager->persist($superUser);
        $this->paymentService->deposit($superUser, $_ENV['START_AMOUNT']);

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, (new \DateTime())->modify('+1 month')->getTimestamp());
        $this->refreshTokenManager->save($refreshToken);

        $manager->flush();
    }
}
