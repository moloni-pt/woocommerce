<?php

namespace Moloni\Controllers;

use Moloni\Enums\Sdr;

/**
 * Moloni product for "SDR - Sistema de Depósito e Reembolso" (Volta) articles.
 *
 * Behaves exactly like a regular {@see Product} except that it is always
 * created as a tax product (type "I" / imposto), with a 0% tax rate and the
 * M99 exemption reason ("Não sujeito ou não tributado"), regardless of the
 * store taxes.
 */
class SdrProduct extends Product
{
    /**
     * Always create the SDR product with a fixed name
     *
     * @return SdrProduct
     */
    protected function setName()
    {
        $this->name = Sdr::NAME;

        return $this;
    }

    /**
     * Force the SDR product to be a tax product (imposto)
     *
     * @return SdrProduct
     */
    protected function setType()
    {
        $this->type = Sdr::PRODUCT_TYPE;
        $this->has_stock = 0;

        return $this;
    }

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
