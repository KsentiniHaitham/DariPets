<?php

namespace App\Serializer;

use App\Entity\User;
use App\Repository\BookingRepository;
use App\Service\ContactMasker;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Masque le numéro de téléphone dans les sorties API, sauf pour :
 * soi-même, un admin, ou un utilisateur lié par une réservation payée.
 */
final class UserNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'USER_PHONE_NORMALIZER_CALLED';

    public function __construct(
        private Security $security,
        private BookingRepository $bookings,
        private ContactMasker $masker,
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && $data instanceof User;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;
        $normalized = $this->normalizer->normalize($data, $format, $context);

        if (\is_array($normalized) && isset($normalized['phone']) && !$this->canSeePhone($data)) {
            $normalized['phone'] = $this->masker->maskPhone($normalized['phone']);
        }

        return $normalized;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [User::class => false];
    }

    private function canSeePhone(User $target): bool
    {
        $current = $this->security->getUser();
        if (!$current instanceof User) {
            return false;
        }
        if ($current === $target || $current->getId() === $target->getId()) {
            return true;
        }
        if (\in_array('ROLE_ADMIN', $current->getRoles(), true)) {
            return true;
        }
        return $this->bookings->hasPaidBookingBetween($current, $target);
    }
}
