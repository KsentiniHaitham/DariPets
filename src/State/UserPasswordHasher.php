<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Notification;
use App\Entity\User;
use App\Service\AppNotifier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Hache le mot de passe en clair avant la persistance d'un User.
 * À l'inscription d'un gardien, notifie les administrateurs.
 */
final class UserPasswordHasher implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $decorated,
        private UserPasswordHasherInterface $passwordHasher,
        private AppNotifier $notifier,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $isNewSitter = $data instanceof User && $data->getId() === null && $data->getType() === User::TYPE_SITTER;

        if ($data instanceof User && $data->getPlainPassword()) {
            $data->setPassword(
                $this->passwordHasher->hashPassword($data, $data->getPlainPassword())
            );
            $data->eraseCredentials();
        }

        $result = $this->decorated->process($data, $operation, $uriVariables, $context);

        if ($isNewSitter) {
            $this->notifier->notifyAdmins(Notification::SITTER_REGISTERED,
                sprintf('👤 Nouveau gardien inscrit : %s', $data->getFullName()), '/admin');
            $this->notifier->flush();
        }

        return $result;
    }
}
