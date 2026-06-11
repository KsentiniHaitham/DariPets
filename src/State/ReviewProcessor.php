<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * À la création d'un avis : fixe l'auteur (utilisateur connecté) et recalcule
 * la note moyenne + le nombre d'avis du gardien évalué.
 */
final class ReviewProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private ReviewRepository $reviews,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Review && $data->getId() === null) {
            $current = $this->security->getUser();
            if ($current instanceof User) {
                $data->setAuthor($current);
            }
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($data instanceof Review && $data->getTarget()) {
            $this->recomputeRating($data->getTarget());
        }

        return $result;
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
