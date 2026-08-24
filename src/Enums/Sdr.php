<?php

namespace Moloni\Enums;

/**
 * "SDR - Sistema de Depósito e Reembolso" (Volta) rules.
 *
 * Products whose reference matches {@see Sdr::REFERENCE} must be issued with a
 * 0% tax rate, the M99 exemption reason and the "I" product type (imposto).
 */
class Sdr
{
    /** Reference that identifies an SDR "Volta" product */
    public const REFERENCE = 'SDR-VOLTA';

    /** Fixed name used when creating the SDR product in Moloni */
    public const NAME = 'SDR Volta';

    /** Exemption reason applied to SDR products (M99 - Não sujeito ou não tributado) */
    public const EXEMPTION_REASON = 'M99';

    /** Product type applied to SDR products (4 - imposto, the "I" article type) */
    public const PRODUCT_TYPE = 4;

    /**
     * Check if a given reference identifies an SDR product
     *
     * @param string|null $reference
     *
     * @return bool
     */
    public static function isReference(?string $reference): bool
    {
        if ($reference === null || $reference === '') {
            return false;
        }

        return strcasecmp(trim($reference), self::REFERENCE) === 0;
    }
}
