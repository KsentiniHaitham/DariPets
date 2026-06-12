<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Report;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * À la création d'un signalement : le rapporteur est TOUJOURS l'utilisateur
 * connecté, et on ne peut pas se signaler soi-même.
 */
final class ReportProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Report && $data->getId() === null) {
            $current = $this->security->getUser();
            if (!$current instanceof User) {
                throw new AccessDeniedHttpException('Authentification requise.');
            }
            if ($data->getReported()?->getId() === $current->getId()) {
                throw new UnprocessableEntityHttpException('Vous ne pouvez pas vous signaler vous-même.');
            }
            $data->setReporter($current);
            $data->setStatus(Report::STATUS_PENDING);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
