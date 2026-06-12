<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Notification;
use App\Entity\User;
use App\Payment\CmiClient;
use App\Repository\BookingRepository;
use App\Service\AppNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private CmiClient $cmi,
        private BookingRepository $bookings,
        private EntityManagerInterface $em,
        private AppNotifier $notifier,
    ) {
    }

    /**
     * Initie le paiement CMI d'une réservation acceptée.
     * Renvoie l'URL de la passerelle + les paramètres signés que le front POSTe.
     */
    #[Route('/api/bookings/{id}/pay', name: 'booking_pay', methods: ['POST'])]
    public function pay(Booking $booking, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $booking->getOwner() !== $user) {
            return $this->json(['detail' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }
        if ($booking->getStatus() !== Booking::STATUS_ACCEPTED) {
            return $this->json(['detail' => 'La réservation doit être acceptée avant paiement.'], Response::HTTP_BAD_REQUEST);
        }

        $locale = $request->getPreferredLanguage(['fr', 'ar']) ?? 'fr';
        $payment = $this->cmi->buildPaymentRequest($booking, $locale);

        return $this->json($payment);
    }

    /**
     * Callback serveur-à-serveur appelé par CMI à l'issue du paiement.
     * Vérifie le HASH puis met à jour le statut de la réservation.
     */
    #[Route('/api/payment/cmi/callback', name: 'cmi_callback', methods: ['POST'])]
    public function callback(Request $request): Response
    {
        $data = $request->request->all();

        if (!$this->cmi->isCallbackValid($data)) {
            return new Response('FAILURE', Response::HTTP_BAD_REQUEST);
        }

        // oid au format "AM-<bookingId>-<timestamp>"
        $oid = (string) ($data['oid'] ?? '');
        $parts = explode('-', $oid);
        $bookingId = isset($parts[1]) ? (int) $parts[1] : 0;
        $booking = $this->bookings->find($bookingId);

        if (!$booking) {
            return new Response('FAILURE', Response::HTTP_NOT_FOUND);
        }

        $approved = ($data['ProcReturnCode'] ?? '') === '00'
            || strtoupper((string) ($data['Response'] ?? '')) === 'APPROVED';

        if ($approved) {
            $booking->setStatus(Booking::STATUS_PAID);
            if ($booking->getSitter()) {
                $this->notifier->notify($booking->getSitter(), Notification::BOOKING_PAID,
                    sprintf('💰 Réservation payée — %s MAD net pour vous', $booking->getSitterPayout()), '/mes-revenus');
            }
            if ($booking->getOwner()) {
                $this->notifier->notify($booking->getOwner(), Notification::BOOKING_PAID,
                    sprintf('💳 Paiement confirmé (%s MAD) — votre réservation est validée', $booking->getTotalPrice()), '/espace');
            }
            $this->em->flush();
        }

        // CMI attend la chaîne "ACTION=POSTAUTH" ou un simple "APPROVED"/"FAILURE"
        return new Response($approved ? 'APPROVED' : 'FAILURE');
    }
}
