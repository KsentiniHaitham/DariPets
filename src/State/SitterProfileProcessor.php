<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Notification;
use App\Entity\PetSitterProfile;
use App\Service\AppNotifier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Notifie les administrateurs quand un gardien soumet sa pièce d'identité (KYC).
 */
final class SitterProfileProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private AppNotifier $notifier,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $kycJustSubmitted = false;
        if ($data instanceof PetSitterProfile && $data->isIdDocumentSubmitted()) {
            $previous = $context['previous_data'] ?? null;
            $kycJustSubmitted = !$previous instanceof PetSitterProfile || !$previous->isIdDocumentSubmitted();
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($kycJustSubmitted) {
            $this->notifier->notifyAdmins(Notification::KYC_SUBMITTED,
                sprintf('🪪 Document KYC soumis par %s — vérification en attente', $data->getUser()?->getFullName() ?? '?'), '/admin');
            $this->notifier->flush();
        }

        return $result;
    }
}
