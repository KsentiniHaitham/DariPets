<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * À la création d'une réservation :
 *  - force le propriétaire = utilisateur connecté,
 *  - calcule le montant total (tarif journalier du gardien × nb de nuits) en MAD.
 */
final class BookingProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        #[Autowire('%env(float:COMMISSION_RATE)%')]
        private float $commissionRate,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Booking && $data->getId() === null) {
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
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
