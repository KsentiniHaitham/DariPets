<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Notifications de l'utilisateur connecté (badge cloche + menu navbar).
 */
#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notifications,
        private EntityManagerInterface $em,
    ) {
    }

    /** Les 20 dernières notifications + compteur de non-lues, en un seul appel. */
    #[Route('', name: 'notifications_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'unread' => $this->notifications->countUnreadFor($user),
            'items' => array_map(static fn (Notification $n) => [
                'id' => $n->getId(),
                'type' => $n->getType(),
                'title' => $n->getTitle(),
                'link' => $n->getLink(),
                'isRead' => $n->isRead(),
                'createdAt' => $n->getCreatedAt()->format(\DATE_ATOM),
            ], $this->notifications->findLatestFor($user)),
        ]);
    }

    /** Marque une notification comme lue. */
    #[Route('/{id}/read', name: 'notification_read', methods: ['POST'])]
    public function markRead(Notification $notification): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($notification->getRecipient() !== $this->getUser()) {
            return $this->json(['error' => 'Cette notification ne vous appartient pas.'], 403);
        }

        $notification->setIsRead(true);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    /** Marque toutes les notifications comme lues. */
    #[Route('/read-all', name: 'notifications_read_all', methods: ['POST'])]
    public function markAllRead(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['updated' => $this->notifications->markAllReadFor($user)]);
    }
}
