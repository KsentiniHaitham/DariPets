<?php

namespace App\Serializer;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Le gardien ne voit l'adresse exacte de la prestation qu'une fois la
 * réservation payée (statuts paid / completed). Le propriétaire et l'admin
 * la voient toujours.
 */
final class BookingNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'BOOKING_ADDRESS_NORMALIZER_CALLED';
    private const ADDRESS_PLACEHOLDER = '🔒 Adresse visible après paiement';

    public function __construct(private Security $security)
    {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && $data instanceof Booking;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;
        $normalized = $this->normalizer->normalize($data, $format, $context);

        if (\is_array($normalized) && !empty($normalized['address']) && !$this->canSeeAddress($data)) {
            $normalized['address'] = self::ADDRESS_PLACEHOLDER;
        }

        return $normalized;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Booking::class => false];
    }

    private function canSeeAddress(Booking $booking): bool
    {
        if (\in_array($booking->getStatus(), [Booking::STATUS_PAID, Booking::STATUS_COMPLETED], true)) {
            return true;
        }

        $current = $this->security->getUser();
        if (!$current instanceof User) {
            return false;
        }
        // Le propriétaire voit toujours sa propre adresse ; l'admin aussi
        return $current->getId() === $booking->getOwner()?->getId()
            || \in_array('ROLE_ADMIN', $current->getRoles(), true);
    }
}
