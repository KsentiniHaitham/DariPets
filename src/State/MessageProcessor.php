<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Message;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * À l'envoi d'un message : l'expéditeur est TOUJOURS l'utilisateur connecté
 * (jamais fourni par le client), et il doit être participant de la conversation.
 *
 * Le corps est stocké tel quel : le masquage des coordonnées (téléphones,
 * e-mails, liens) se fait dynamiquement À L'AFFICHAGE (MessageNormalizer),
 * pour pouvoir révéler le contenu une fois une réservation payée (modèle Airbnb).
 */
final class MessageProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
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
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
