<?php

namespace App\Service;

/**
 * Masque les coordonnées (téléphones, e-mails, liens) dans les textes échangés
 * avant paiement, pour empêcher le contournement de la plateforme.
 */
final class ContactMasker
{
    public const PLACEHOLDER = '[🔒 masqué — visible après paiement]';

    public function mask(string $text): string
    {
        // Numéros marocains : +212 6XX..., 06XX..., 07..., 05..., avec séparateurs éventuels
        $text = preg_replace(
            '/(?:\+?212|0)[\s.\-]?[567](?:[\s.\-]?\d){8}/u',
            self::PLACEHOLDER,
            $text
        );

        // Toute séquence de 8 chiffres ou plus (anti-contournement "0 6 1 2...")
        $text = preg_replace(
            '/\d(?:[\s.\-]{0,2}\d){7,}/u',
            self::PLACEHOLDER,
            $text
        );

        // E-mails
        $text = preg_replace(
            '/[\w.+\-]+\s?(?:@|\[at\]|\(at\))\s?[\w\-]+\s?(?:\.|\[dot\]|\(dot\))\s?\w{2,}/iu',
            self::PLACEHOLDER,
            $text
        );

        // URLs et liens (images, localisation Google Maps, réseaux sociaux…)
        $text = preg_replace(
            '/(?:https?:\/\/|www\.)\S+/iu',
            self::PLACEHOLDER,
            $text
        );

        // Mentions de réseaux de contact direct (whatsapp, instagram, telegram…)
        $text = preg_replace(
            '/\b(?:whatsapp|whats app|wattsap|insta(?:gram)?|telegram|snap(?:chat)?|facebook|messenger)\b[\s:.\-]*@?[\w.]*/iu',
            self::PLACEHOLDER,
            $text
        );

        return $text;
    }

    /** Masque un numéro de téléphone en gardant les 2 premiers chiffres : « 06 •• •• •• •• » */
    public function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }
        return mb_substr($phone, 0, 2) . ' •• •• •• ••';
    }
}
