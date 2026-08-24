<?php

namespace Moloni\Controllers;

use Moloni\Enums\Sdr;

/**
 * Order line for "SDR - Sistema de Depósito e Reembolso" (Volta) products.
 *
 * Behaves exactly like a regular {@see OrderProduct} line except that it is
 * always issued at 0% with the M99 exemption reason ("Não sujeito ou não
 * tributado") and its Moloni product is created as an {@see SdrProduct}
 * (with the "I" AT product category), regardless of the store taxes.
 */
class OrderSdr extends OrderProduct
{
    /**
     * Force the SDR tax treatment on the document line
     *
     * @return OrderSdr
     */
    protected function setTaxes()
    {
        $this->taxes = [];
        $this->exemption_reason = Sdr::EXEMPTION_REASON;

        return $this;
    }

    /**
     * Build the SDR Moloni product for this order line
     *
     * @param \WC_Product $wcProduct
     *
     * @return Product
     */
    protected function makeMoloniProduct($wcProduct): Product
    {
        return new SdrProduct($wcProduct);
    }
}
