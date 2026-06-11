<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\PetSitterProfile;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\PetSitterProfileRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Back-office réservé aux administrateurs (ROLE_ADMIN).
 */
#[Route('/api/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private UserRepository $users,
        private PetSitterProfileRepository $sitters,
        private BookingRepository $bookings,
        private ReviewRepository $reviews,
        private EntityManagerInterface $em,
    ) {
    }

    /** Statistiques globales pour le tableau de bord admin. */
    #[Route('/stats', name: 'admin_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $allBookings = $this->bookings->findAll();
        $allUsers = $this->users->findAll();
        $allSitters = $this->sitters->findAll();
        $allReviews = $this->reviews->findAll();

        // --- 6 derniers mois (clés YYYY-MM + libellés courts) ---
        $months = [];
        $monthLabels = [];
        $frMonths = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        for ($i = 5; $i >= 0; $i--) {
            $d = new \DateTimeImmutable("first day of -$i months");
            $key = $d->format('Y-m');
            $months[$key] = 0;
            $monthLabels[$key] = $frMonths[(int) $d->format('n')];
        }

        $registrations = $months;
        $bookingsByMonth = $months;
        $revenueByMonth = $months;
        $commissionByMonth = $months;

        $revenue = 0.0;     // volume d'affaires (total payé par les propriétaires)
        $commission = 0.0;  // revenus de la plateforme (commissions)
        $statusCount = [];
        foreach ($allBookings as $b) {
            $statusCount[$b->getStatus()] = ($statusCount[$b->getStatus()] ?? 0) + 1;
            $mk = $b->getCreatedAt()->format('Y-m');
            if (isset($bookingsByMonth[$mk])) {
                $bookingsByMonth[$mk]++;
            }
            if (in_array($b->getStatus(), [Booking::STATUS_PAID, Booking::STATUS_COMPLETED], true)) {
                $revenue += (float) $b->getTotalPrice();
                $commission += (float) $b->getCommissionAmount();
                if (isset($revenueByMonth[$mk])) {
                    $revenueByMonth[$mk] += (float) $b->getTotalPrice();
                    $commissionByMonth[$mk] += (float) $b->getCommissionAmount();
                }
            }
        }

        foreach ($allUsers as $u) {
            $mk = $u->getCreatedAt()->format('Y-m');
            if (isset($registrations[$mk])) {
                $registrations[$mk]++;
            }
        }

        // Gardiens par ville
        $byCity = [];
        foreach ($allSitters as $p) {
            $city = $p->getUser()?->getCity()?->getName() ?? '—';
            $byCity[$city] = ($byCity[$city] ?? 0) + 1;
        }
        arsort($byCity);

        // Répartition des notes (1 à 5)
        $ratingDist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($allReviews as $r) {
            $ratingDist[$r->getRating()] = ($ratingDist[$r->getRating()] ?? 0) + 1;
        }

        return $this->json([
            'users' => count($allUsers),
            'owners' => count($this->users->findBy(['type' => User::TYPE_OWNER])),
            'sitters' => count($this->users->findBy(['type' => User::TYPE_SITTER])),
            'sittersVerified' => count($this->sitters->findBy(['verified' => true])),
            'sittersPending' => count($this->sitters->findBy(['verified' => false])),
            'bookings' => count($allBookings),
            'bookingsByStatus' => $statusCount,
            'reviews' => count($allReviews),
            'revenueMad' => number_format($revenue, 2, '.', ''),
            'commissionMad' => number_format($commission, 2, '.', ''),
            'charts' => [
                'monthLabels' => array_values($monthLabels),
                'registrations' => array_values($registrations),
                'bookingsByMonth' => array_values($bookingsByMonth),
                'revenueByMonth' => array_map(fn ($v) => round($v, 2), array_values($revenueByMonth)),
                'commissionByMonth' => array_map(fn ($v) => round($v, 2), array_values($commissionByMonth)),
                'sittersByCity' => ['labels' => array_keys($byCity), 'data' => array_values($byCity)],
                'ratingDistribution' => array_values($ratingDist),
                'bookingStatusKeys' => array_keys($statusCount),
                'bookingStatusValues' => array_values($statusCount),
            ],
        ]);
    }

    /** Vérifie / dé-vérifie un profil gardien. */
    #[Route('/sitters/{id}/verify', name: 'admin_sitter_verify', methods: ['POST'])]
    public function verifySitter(PetSitterProfile $profile): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $profile->setVerified(!$profile->isVerified());
        $this->em->flush();

        return $this->json(['id' => $profile->getId(), 'verified' => $profile->isVerified()]);
    }
}
