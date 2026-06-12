<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Entity\Notification;
use App\Entity\User;
use App\Service\AppNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * À la création d'une réservation :
 *  - force le propriétaire = utilisateur connecté,
 *  - calcule le montant total (tarif journalier du gardien × nb de nuits) en MAD,
 *  - notifie le gardien.
 * Au changement de statut (Patch) : notifie la partie concernée.
 */
final class BookingProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private AppNotifier $notifier,
        #[Autowire('%env(float:COMMISSION_RATE)%')]
        private float $commissionRate,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $isNew = $data instanceof Booking && $data->getId() === null;
        $previousStatus = null;

        if ($isNew) {
            $current = $this->security->getUser();
            if ($current instanceof User) {
                $data->setOwner($current);
            }

            $profile = $data->getSitter()?->getSitterProfile();
            $dailyRate = $profile ? (float) $profile->getDailyRate() : 0.0;
            $total = $dailyRate * max(1, $data->getNights());
            $data->setTotalPrice(number_format($total, 2, '.', ''));
            $data->applyCommission($this->commissionRate);
            $data->setStatus(Booking::STATUS_PENDING);
        } elseif ($data instanceof Booking && ($context['previous_data'] ?? null) instanceof Booking) {
            $previousStatus = $context['previous_data']->getStatus();
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($data instanceof Booking) {
            if ($isNew) {
                $this->notifyNewRequest($data);
            } elseif ($previousStatus !== null && $previousStatus !== $data->getStatus()) {
                $this->notifyStatusChange($data, $previousStatus);
            }
            $this->notifier->flush();
        }

        return $result;
    }

    private function notifyNewRequest(Booking $b): void
    {
        if (!$b->getSitter() || !$b->getOwner()) {
            return;
        }
        $this->notifier->notify(
            $b->getSitter(),
            Notification::BOOKING_REQUEST,
            sprintf('📩 Nouvelle demande de réservation de %s (%d nuit(s))', $b->getOwner()->getFullName(), $b->getNights()),
            '/espace'
        );
    }

    private function notifyStatusChange(Booking $b, string $from): void
    {
        $owner = $b->getOwner();
        $sitter = $b->getSitter();
        if (!$owner || !$sitter) {
            return;
        }

        switch ($b->getStatus()) {
            case Booking::STATUS_ACCEPTED:
                $this->notifier->notify($owner, Notification::BOOKING_ACCEPTED,
                    sprintf('✅ %s a accepté votre demande — payez pour confirmer', $sitter->getFullName()), '/espace');
                break;
            case Booking::STATUS_REJECTED:
                $this->notifier->notify($owner, Notification::BOOKING_REJECTED,
                    sprintf('❌ %s a refusé votre demande de réservation', $sitter->getFullName()), '/recherche');
                break;
            case Booking::STATUS_CANCELLED:
                // Notifie la partie qui n'a pas annulé (l'acteur = utilisateur connecté)
                $actor = $this->security->getUser();
                $other = $actor === $owner ? $sitter : $owner;
                $this->notifier->notify($other, Notification::BOOKING_CANCELLED,
                    sprintf('❌ La réservation du %s a été annulée', $b->getStartDate()?->format('d/m/Y') ?? ''), '/espace');
                break;
            case Booking::STATUS_PAID:
                $this->notifier->notify($sitter, Notification::BOOKING_PAID,
                    sprintf('💰 Réservation payée — %s MAD net pour vous', $b->getSitterPayout()), '/mes-revenus');
                $this->notifier->notify($owner, Notification::BOOKING_PAID,
                    sprintf('💳 Paiement confirmé (%s MAD) — votre réservation est validée', $b->getTotalPrice()), '/espace');
                break;
            case Booking::STATUS_COMPLETED:
                $this->notifier->notify($owner, Notification::BOOKING_COMPLETED,
                    sprintf('🐾 Prestation terminée — partagez votre avis sur %s', $sitter->getFullName()), '/espace');
                break;
        }
    }
}
