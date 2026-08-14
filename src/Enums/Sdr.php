<?php

namespace Moloni\Enums;

/**
 * "SDR - Sistema de Depósito e Reembolso" (Volta) rules.
 *
 * Products whose reference matches {@see Sdr::REFERENCE} must be issued with a
 * 0% tax rate, the M99 exemption reason and the "I" AT product category
 * (impostos, taxas e encargos parafiscais).
 */
class Sdr
{
    /** Reference that identifies an SDR "Volta" product */
    public const REFERENCE = 'SDR-VOLTA';

    /** Exemption reason applied to SDR products (M99 - Não sujeito ou não tributado) */
    public const EXEMPTION_REASON = 'M99';

    /** AT product category applied to SDR products (I - Impostos, taxas e encargos parafiscais) */
    public const AT_PRODUCT_CATEGORY = 'I';

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
