<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /** Vrai si une réservation payée (ou terminée) existe entre ces deux utilisateurs. */
    public function hasPaidBookingBetween(User $a, User $b): bool
    {
        return null !== $this->createQueryBuilder('b')
            ->select('b.id')
            ->where('b.status IN (:statuses)')
            ->andWhere('(b.owner = :a AND b.sitter = :b) OR (b.owner = :b AND b.sitter = :a)')
            ->setParameter('statuses', [Booking::STATUS_PAID, Booking::STATUS_COMPLETED])
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
