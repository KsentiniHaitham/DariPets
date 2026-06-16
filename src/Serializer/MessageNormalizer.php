<?php

namespace App\Serializer;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Service\ContactMasker;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Masquage « à l'affichage » des coordonnées dans les messages (modèle Airbnb) :
 * tant qu'aucune réservation payée n'existe entre le lecteur et l'autre
 * participant, les téléphones / e-mails / liens sont masqués. Après paiement,
 * le message original est révélé. L'admin voit toujours le texte brut (modération).
 */
final class MessageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'MESSAGE_MASK_NORMALIZER_CALLED';

    /** @var array<string, bool> Cache par paire d'utilisateurs (évite N requêtes par conversation). */
    private array $paidCache = [];

    public function __construct(
        private Security $security,
        private BookingRepository $bookings,
        private ContactMasker $masker,
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && $data instanceof Message;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;
        $normalized = $this->normalizer->normalize($data, $format, $context);

        if (\is_array($normalized) && isset($normalized['body']) && $this->shouldMask($data)) {
            $normalized['body'] = $this->masker->mask($normalized['body']);
        }

        return $normalized;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Message::class => false];
    }

    private function shouldMask(Message $message): bool
    {
        $viewer = $this->security->getUser();
        if (!$viewer instanceof User) {
            return true; // par défaut on masque
        }
        if (\in_array('ROLE_ADMIN', $viewer->getRoles(), true)) {
            return false; // l'admin voit tout (modération / signalements)
        }

        $conversation = $message->getConversation();
        if ($conversation === null) {
            return true;
        }

        // Révélé dès qu'une réservation payée lie le lecteur à un autre participant
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getId() === $viewer->getId()) {
                continue;
            }
            if ($this->hasPaidBooking($viewer, $participant)) {
                return false;
            }
        }

        return true;
    }

    private function hasPaidBooking(User $a, User $b): bool
    {
        $key = min($a->getId(), $b->getId()) . '-' . max($a->getId(), $b->getId());

        return $this->paidCache[$key] ??= $this->bookings->hasPaidBookingBetween($a, $b);
    }
}
