<?php

namespace Moloni\Controllers;

use Moloni\Enums\Sdr;

/**
 * Moloni product for "SDR - Sistema de Depósito e Reembolso" (Volta) articles.
 *
 * Behaves exactly like a regular {@see Product} except that it is always
 * created with the "I" AT product category (impostos, taxas e encargos
 * parafiscais), a 0% tax rate and the M99 exemption reason ("Não sujeito ou
 * não tributado"), regardless of the store taxes.
 */
class SdrProduct extends Product
{
    /** @var string */
    protected $at_product_category = Sdr::AT_PRODUCT_CATEGORY;

    /**
     * Force the SDR tax treatment on the product
     *
     * @return SdrProduct
     */
    protected function setTaxes()
    {
        $this->taxes = [];
        $this->exemption_reason = Sdr::EXEMPTION_REASON;

        return $this;
    }
}
