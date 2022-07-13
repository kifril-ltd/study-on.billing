<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function add(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects with filters
     * @var User $user
     */
    public function findUserTransactionsByFilters($user, array $filters): array
    {
        $query = $this->createQueryBuilder('t')
            ->leftJoin('t.course', 'c')
            ->andWhere('t.customer = :user_id')
            ->setParameter('user_id', $user->getId())
            ->orderBy('t.createdAt', 'DESC');

        if ($filters['type']) {
            $query->andWhere('t.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if ($filters['course_code']) {
            $query->andWhere('c.code = :course_code')
                ->setParameter('course_code', $filters['course_code']);
        }

        if ($filters['skip_expired']) {
            $query->andWhere('t.expiresAt IS NULL OR t.expiresAt >= :today')
                ->setParameter('today', new \DateTimeImmutable());
        }

        return $query->getQuery()->getResult();
    }

    public function findRecentlyExpiredTransactions($user)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.customer = :user_id')
            ->andWhere('t.type = 1')
            ->andWhere('t.expiresAt >= :today AND DATE_DIFF(t.expiresAt, :today) <= 1')
            ->setParameter('today', new \DateTimeImmutable())
            ->setParameter('user_id', $user->getId())
            ->getQuery()
            ->getResult();

    }

    public function getPayStatisticPerMonth()
    {
        $dql = "
            SELECT c.title, 
                   (CASE WHEN c.type = 1 THEN 'Аренда' ELSE 'Покупка' END) as course_type, 
                   COUNT(t.id) as transaction_count, 
                   SUM(t.amount) as total_amount
            FROM App\\Entity\\Transaction t JOIN App\\Entity\\Course c WITH t.course = c.id
            WHERE t.type = 1 AND t.createdAt BETWEEN DATE_SUB(CURRENT_DATE(), 1, 'MONTH') AND CURRENT_DATE()
            GROUP BY c.title, c.type
        ";

        return $this->_em->createQuery($dql)->getResult();

    }
}
