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

    /** Marque comme lus (+ horodatage) tous les messages reçus dans une conversation. */
    public function markConversationAsRead(Conversation $conversation, User $user): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', 'true')
            ->set('m.readAt', ':now')
            ->where('m.conversation = :conversation')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = false')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Marque comme « distribués » tous les messages reçus mais non encore distribués.
     * Appelé par le heartbeat : signifie que l'app du destinataire est en ligne et les a reçus.
     */
    public function markDeliveredFor(User $user): int
    {
        // Sous-requête : ids des conversations auxquelles l'utilisateur participe
        // (DQL UPDATE n'autorise pas les JOIN, on passe donc par un IN).
        $convIds = $this->getEntityManager()->createQueryBuilder()
            ->select('c2.id')
            ->from(Conversation::class, 'c2')
            ->join('c2.participants', 'p2')
            ->where('p2 = :user')
            ->getDQL();

        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.deliveredAt', ':now')
            ->where('m.deliveredAt IS NULL')
            ->andWhere('m.sender != :user')
            ->andWhere("m.conversation IN ($convIds)")
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
