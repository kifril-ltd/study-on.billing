<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentService
{
    private const OPERATION_TYPE = [
        'payment' => 1,
        'deposit' => 2
    ];

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function deposit(User $user, float $amount)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $transaction = new Transaction();

            $transaction->setCustomer($user);
            $transaction->setType(self::OPERATION_TYPE['deposit']);
            $transaction->setAmount($amount);

            $user->setBalance($user->getBalance() + $amount);

            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->em->getConnection()->rollBack();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function payment(User $user, Course $course): Transaction
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if ($user->getBalance() < $course->getPrice()) {
                throw new \Exception( 'На счету недостаточно средств', Response::HTTP_NOT_ACCEPTABLE);
            }
            $transaction = new Transaction();

            $transaction->setCustomer($user);
            $transaction->setType(self::OPERATION_TYPE['payment']);
            $transaction->setAmount($course->getPrice());
            $transaction->setCourse($course);

            if ($course->getType() === 'rent') {
                $expiresAt = (new \DateTimeImmutable())->add(new DateInterval('P1W'));
                $transaction->setExpiresAt($expiresAt);
            }

            $user->setBalance($user->getBalance() - $course->getPrice());

            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->em->getConnection()->rollBack();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }

        return $transaction;
    }

}