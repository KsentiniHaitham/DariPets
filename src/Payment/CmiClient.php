<?php

namespace App\Payment;

use App\Entity\Booking;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Intégration de la passerelle de paiement CMI (Centre Monétique Interbancaire, Maroc).
 *
 * Le paiement se fait via une page hostée : on construit un ensemble de paramètres,
 * on calcule un HASH (SHA-512, algo "ver3") avec la clé marchande (storeKey), puis le
 * navigateur POSTe le formulaire vers la passerelle. À la fin, CMI rappelle CALLBACK_URL.
 *
 * Devise : 504 = MAD (dirham marocain), ISO 4217.
 */
final class CmiClient
{
    private const CURRENCY_MAD = '504';

    public function __construct(
        #[Autowire('%env(CMI_GATEWAY_URL)%')] private string $gatewayUrl,
        #[Autowire('%env(CMI_CLIENT_ID)%')] private string $clientId,
        #[Autowire('%env(CMI_STORE_KEY)%')] private string $storeKey,
        #[Autowire('%env(CMI_OK_URL)%')] private string $okUrl,
        #[Autowire('%env(CMI_FAIL_URL)%')] private string $failUrl,
        #[Autowire('%env(CMI_CALLBACK_URL)%')] private string $callbackUrl,
    ) {
    }

    /**
     * Construit les paramètres (avec HASH) à POSTer vers la passerelle CMI.
     *
     * @return array{action: string, params: array<string, string>}
     */
    public function buildPaymentRequest(Booking $booking, string $locale = 'fr'): array
    {
        $oid = sprintf('AM-%d-%d', $booking->getId(), time());

        $params = [
            'clientid' => $this->clientId,
            'storetype' => '3D_PAY_HOSTING',
            'trantype' => 'PreAuth',
            'amount' => number_format((float) $booking->getTotalPrice(), 2, '.', ''),
            'currency' => self::CURRENCY_MAD,
            'oid' => $oid,
            'okUrl' => $this->okUrl,
            'failUrl' => $this->failUrl,
            'callbackUrl' => $this->callbackUrl,
            'lang' => $locale === 'ar' ? 'ar' : 'fr',
            'email' => $booking->getOwner()?->getEmail() ?? '',
            'BillToName' => $booking->getOwner()?->getFullName() ?? '',
            'rnd' => bin2hex(random_bytes(8)),
            'hashAlgorithm' => 'ver3',
            'encoding' => 'UTF-8',
        ];

        $params['HASH'] = $this->computeHash($params);

        return ['action' => $this->gatewayUrl, 'params' => $params];
    }

    /**
     * Vérifie le HASH renvoyé par CMI lors du callback.
     *
     * @param array<string, string> $data
     */
    public function isCallbackValid(array $data): bool
    {
        $received = $data['HASH'] ?? '';
        if ($received === '') {
            return false;
        }
        $computed = $this->computeHash($data);

        return hash_equals($computed, $received);
    }

    /**
     * Hash CMI "ver3" : tri des clés (insensible à la casse), concaténation des valeurs
     * échappées séparées par "|", ajout de la storeKey échappée, SHA-512 puis base64.
     *
     * @param array<string, string> $params
     */
    private function computeHash(array $params): string
    {
        $excluded = ['hash', 'encoding'];
        $keys = array_keys($params);
        $keys = array_filter($keys, fn ($k) => !in_array(strtolower($k), $excluded, true));
        usort($keys, fn ($a, $b) => strcasecmp($a, $b));

        $plaintext = '';
        foreach ($keys as $key) {
            $value = (string) ($params[$key] ?? '');
            $plaintext .= $this->escape($value) . '|';
        }
        $plaintext .= $this->escape($this->storeKey);

        return base64_encode(hash('sha512', $plaintext, true));
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '|'], ['\\\\', '\\|'], $value);
    }
}
