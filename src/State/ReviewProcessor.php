<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Entity\Notification;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\ReviewRepository;
use App\Service\AppNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * À la création d'un avis : fixe l'auteur (utilisateur connecté), exige une
 * réservation terminée entre l'auteur et le gardien (anti faux avis), puis
 * recalcule la note moyenne + le nombre d'avis du gardien évalué.
 */
final class ReviewProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private ReviewRepository $reviews,
        private BookingRepository $bookings,
        private EntityManagerInterface $em,
        private AppNotifier $notifier,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Review && $data->getId() === null) {
            $current = $this->security->getUser();
            if ($current instanceof User) {
                $data->setAuthor($current);

                // Anti faux avis : une prestation terminée est requise
                $target = $data->getTarget();
                if ($target && !$this->hasCompletedBooking($current, $target)) {
                    throw new UnprocessableEntityHttpException(
                        'Vous ne pouvez laisser un avis qu\'après une réservation terminée avec ce gardien.'
                    );
                }
            }
        }

        $isNew = $data instanceof Review && $data->getId() === null;
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($data instanceof Review && $data->getTarget()) {
            $this->recomputeRating($data->getTarget());

            if ($isNew) {
                $this->notifier->notify($data->getTarget(), Notification::REVIEW_RECEIVED,
                    sprintf('⭐ Nouvel avis %d/5 de %s', $data->getRating(), $data->getAuthor()?->getFullName() ?? 'un client'), '/mon-profil');
                if ($data->getRating() <= 2) {
                    $this->notifier->notifyAdmins(Notification::REVIEW_NEGATIVE,
                        sprintf('⚠️ Avis négatif (%d/5) sur %s', $data->getRating(), $data->getTarget()->getFullName()), '/admin');
                }
                $this->notifier->flush();
            }
        }

        return $result;
    }

    private function hasCompletedBooking(User $owner, User $sitter): bool
    {
        return null !== $this->bookings->findOneBy([
            'owner' => $owner,
            'sitter' => $sitter,
            'status' => Booking::STATUS_COMPLETED,
        ]);
    }

    private function recomputeRating(User $sitter): void
    {
        $profile = $sitter->getSitterProfile();
        if (!$profile) {
            return;
        }

        $list = $this->reviews->findBy(['target' => $sitter]);
        $count = count($list);
        $avg = $count > 0
            ? array_sum(array_map(fn (Review $r) => $r->getRating(), $list)) / $count
            : 0.0;

        $profile->setReviewCount($count);
        $profile->setRating(round($avg, 1));
        $this->em->flush();
    }
}
