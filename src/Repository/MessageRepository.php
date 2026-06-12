<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /** Nombre de messages non lus reçus par cet utilisateur (toutes conversations). */
    public function countUnreadFor(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->where('p = :user')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Marque comme lus tous les messages reçus par cet utilisateur dans une conversation. */
    public function markConversationAsRead(Conversation $conversation, User $user): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', 'true')
            ->where('m.conversation = :conversation')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = false')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
