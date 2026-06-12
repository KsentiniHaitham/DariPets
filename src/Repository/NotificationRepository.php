<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return Notification[] Les plus récentes d'abord */
    public function findLatestFor(User $user, int $limit = 20): array
    {
        return $this->findBy(['recipient' => $user], ['createdAt' => 'DESC'], $limit);
    }

    public function countUnreadFor(User $user): int
    {
        return $this->count(['recipient' => $user, 'isRead' => false]);
    }

    public function markAllReadFor(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
