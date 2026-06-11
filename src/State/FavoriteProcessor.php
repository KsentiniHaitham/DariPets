<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Favorite;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Force le propriétaire du favori = utilisateur connecté.
 */
final class FavoriteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Favorite && $data->getOwner() === null) {
            $current = $this->security->getUser();
            if ($current instanceof User) {
                $data->setOwner($current);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
