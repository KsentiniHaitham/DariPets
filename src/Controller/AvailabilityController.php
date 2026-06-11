<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AvailabilityController extends AbstractController
{
    public function __construct(private BookingRepository $bookings)
    {
    }

    /**
     * Renvoie les IDs des gardiens (users) OCCUPÉS sur la période [from, to]
     * (réservations acceptées ou payées qui chevauchent la période).
     * Le front exclut ces gardiens des résultats de recherche.
     */
    #[Route('/api/sitters/busy', name: 'sitters_busy', methods: ['GET'])]
    public function busy(Request $request): JsonResponse
    {
        try {
            $from = new \DateTimeImmutable($request->query->get('from', 'now'));
            $to = new \DateTimeImmutable($request->query->get('to', 'now'));
        } catch (\Exception) {
            return $this->json(['detail' => 'Dates invalides.'], 400);
        }

        $rows = $this->bookings->createQueryBuilder('b')
            ->select('DISTINCT IDENTITY(b.sitter) AS sitterId')
            ->where('b.status IN (:statuses)')
            ->andWhere('b.startDate <= :to AND b.endDate >= :from')
            ->setParameter('statuses', [Booking::STATUS_ACCEPTED, Booking::STATUS_PAID])
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        return $this->json(['busySitterIds' => array_map(fn ($r) => (int) $r['sitterId'], $rows)]);
    }
}
