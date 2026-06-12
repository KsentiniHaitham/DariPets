<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée les notifications internes. flush() est laissé à l'appelant
 * (processor/contrôleur) sauf via notifyAndFlush().
 */
final class AppNotifier
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $users,
    ) {
    }

    public function notify(User $recipient, string $type, string $title, ?string $link = null): Notification
    {
        $n = (new Notification())
            ->setRecipient($recipient)
            ->setType($type)
            ->setTitle($title)
            ->setLink($link);
        $this->em->persist($n);

        return $n;
    }

    /** Notifie tous les administrateurs. */
    public function notifyAdmins(string $type, string $title, ?string $link = null): void
    {
        foreach ($this->users->findAll() as $user) {
            if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $this->notify($user, $type, $title, $link);
            }
        }
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}
