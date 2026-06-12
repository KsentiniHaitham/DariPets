<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Service\ContactMasker;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * À l'envoi d'un message : l'expéditeur est TOUJOURS l'utilisateur connecté
 * (jamais fourni par le client), et il doit être participant de la conversation.
 * Tant qu'aucune réservation payée n'existe entre les participants, les
 * coordonnées (téléphones, e-mails, liens) sont masquées dans le corps du message.
 */
final class MessageProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private BookingRepository $bookings,
        private ContactMasker $masker,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Message && $data->getId() === null) {
            $current = $this->security->getUser();
            if (!$current instanceof User) {
                throw new AccessDeniedHttpException('Authentification requise.');
            }
            $conversation = $data->getConversation();
            if (!$conversation?->getParticipants()->contains($current)) {
                throw new AccessDeniedHttpException('Vous ne participez pas à cette conversation.');
            }
            $data->setSender($current);

            // Anti-contournement : masque les coordonnées tant que rien n'est payé
            if (!$this->hasPaidBookingWithOtherParticipant($current, $conversation->getParticipants())) {
                $data->setBody($this->masker->mask($data->getBody()));
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /** @param iterable<User> $participants */
    private function hasPaidBookingWithOtherParticipant(User $current, iterable $participants): bool
    {
        foreach ($participants as $participant) {
            if ($participant !== $current && $this->bookings->hasPaidBookingBetween($current, $participant)) {
                return true;
            }
        }
        return false;
    }
}
