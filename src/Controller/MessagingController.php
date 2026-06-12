<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoints utilitaires de messagerie : compteur de non-lus + marquage comme lu.
 */
#[Route('/api')]
class MessagingController extends AbstractController
{
    public function __construct(private MessageRepository $messages)
    {
    }

    /**
     * Nombre de messages non lus de l'utilisateur connecté (pour le badge navbar).
     * priority: 10 — doit passer avant la route API Platform /api/messages/{id},
     * sinon « unread-count » est interprété comme un id de message (500).
     */
    #[Route('/messages/unread-count', name: 'messages_unread_count', methods: ['GET'], priority: 10)]
    public function unreadCount(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['count' => $this->messages->countUnreadFor($user)]);
    }

    /** Marque tous les messages reçus d'une conversation comme lus. */
    #[Route('/conversations/{id}/read', name: 'conversation_mark_read', methods: ['POST'])]
    public function markRead(Conversation $conversation): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        if (!$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Vous ne participez pas à cette conversation.'], 403);
        }

        $updated = $this->messages->markConversationAsRead($conversation, $user);

        return $this->json(['updated' => $updated]);
    }
}
