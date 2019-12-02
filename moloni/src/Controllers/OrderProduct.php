<?php

namespace Moloni\Controllers;

use Moloni\Error;
use Moloni\Tools;
use WC_Order;
use WC_Order_Item_Product;
use WC_Tax;
use Moloni\Curl;

class OrderProduct
{

    /** @var int */
    public $product_id = 0;

    /** @var int */
    private $order;

    /**
     * @var WC_Order_Item_Product
     */
    private $product;

    /** @var WC_Order */
    private $wc_order;

    /** @var array */
    private $taxes = [];

    /** @var float */
    public $qty;

    /** @var float */
    public $price;

    /** @var string */
    private $exemption_reason;

    /** @var string */
    private $name;

    /** @var string */
    private $summary;

    /** @var float */
    private $discount;

    /** @var int */
    private $warehouse_id = 0;

    /**
     * OrderProduct constructor.
     * @param WC_Order_Item_Product $product
     * @param WC_Order $wcOrder
     * @param int $order
     */
    public function __construct($product, $wcOrder, $order = 0)
    {
        $this->product = $product;
        $this->wc_order = $wcOrder;
        $this->order = $order;
    }

    /**
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this
            ->setName()
            ->setPrice()
            ->setQty()
            ->setSummary()
            ->setProductId()
            ->setDiscount()
            ->setTaxes()
            ->setWarehouse();

        return $this;
    }

    public function setName()
    {
        $this->name = $this->product->get_name();
        return $this;
    }

    /**
     * @param null|string $summary
     * @return $this
     */
    public function setSummary($summary = null)
    {
        if ($summary) {
            $this->summary = $summary;
        } else {
            $this->summary .= $this->getSummaryVariationAttributes();

            if (!empty($this->summary)) {
                $this->summary .= "\n";
            }

            $this->summary .= $this->getSummaryExtraProductOptions();
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getSummaryVariationAttributes()
    {
        $summary = '';

        if ($this->product->get_variation_id() > 0) {
            $product = wc_get_product($this->product->get_variation_id());
            $attributes = $product->get_attributes();
            if (is_array($attributes) && !empty($attributes)) {
                $summary = wc_get_formatted_variation($attributes, true);
            }
        }

        return $summary;
    }

    /**
     * @return string
     */
    private function getSummaryExtraProductOptions()
    {
        $summary = '';
        $checkEPO = $this->product->get_meta('_tmcartepo_data', true);
        $extraProductOptions = maybe_unserialize($checkEPO);

        if ($extraProductOptions && is_array($extraProductOptions)) {
            foreach ($extraProductOptions as $extraProductOption) {
                if (isset($extraProductOption['name'], $extraProductOption['value'])) {

                    if (!empty($summary)) {
                        $summary .= "\n";
                    }

                    $summary .= $extraProductOption['name'] . ' ' . $extraProductOption['value'];
                }
            }
        }

        return $summary;
    }

    /**
     * @return OrderProduct
     */
    public function setPrice()
    {
        $this->price = (float)$this->product->get_subtotal() / (float)$this->product->get_quantity();

        $refundedValue = $this->wc_order->get_total_refunded_for_item($this->product->get_id());
        if ((float)$refundedValue > 0) {
            $this->price -= (float)$refundedValue;
        }

        return $this;
    }

    /**
     * @return OrderProduct
     */
    public function setQty()
    {
        $this->qty = (float)$this->product->get_quantity();

        $refundedQty = $this->wc_order->get_qty_refunded_for_item($this->product->get_id());
        if ((float)$refundedQty > 0) {
            $this->qty -= (float)$refundedQty;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setProductId()
    {
        $product = new Product($this->product->get_product());

        if (!$product->loadByReference()) {
            $product->create();
        }

        $this->product_id = $product->getProductId();

        return $this;
    }


    /**
     * Set the discount in percentage
     * @return $this
     */
    private function setDiscount()
    {
        $this->discount = (100 - (((float)$this->product->get_total() * 100) / (float)$this->product->get_subtotal()));

        if ($this->discount > 100) {
            $this->discount = 100;
        }

        if ($this->discount < 0) {
            $this->discount = 0;
        }

        return $this;
    }

    /**
     * Set the taxes of a product
     * @throws Error
     */
    private function setTaxes()
    {
        $taxRate = 0;
        $taxes = $this->product->get_taxes();
        foreach ($taxes['subtotal'] as $taxId => $value) {
            if (!empty($value)) {
                $taxRate = preg_replace('/[^0-9.]/', '', WC_Tax::get_rate_percent($taxId));
                if ((float)$taxRate > 0) {
                    $this->taxes[] = $this->setTax($taxRate);
                }
            }
        }

        if (empty($this->taxes) || (float)$taxRate === 0) {
            $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
        }

        return $this;
    }

    /**
     * @param float $taxRate Tax Rate in percentage
     * @return array
     * @throws Error
     */
    private function setTax($taxRate)
    {
        $tax = [];
        $tax['tax_id'] = Tools::getTaxIdFromRate((float)$taxRate);
        $tax['value'] = $taxRate;

        return $tax;
    }

    /**
     * @param bool|int $warehouseId
     * @return OrderProduct
     */
    private function setWarehouse($warehouseId = false)
    {
        if ((int)$warehouseId > 0) {
            $this->warehouse_id = $warehouseId;
            return $this;
        }

        if (defined('MOLONI_PRODUCT_WAREHOUSE') && (int)MOLONI_PRODUCT_WAREHOUSE > 0) {
            $this->warehouse_id = (int)MOLONI_PRODUCT_WAREHOUSE;
        }


        return $this;
    }

    /**
     * @return array
     */
    public function mapPropsToValues()
    {
        $values = [];

        $values['product_id'] = $this->product_id;
        $values['name'] = $this->name;
        $values['summary'] = $this->summary;
        $values['qty'] = $this->qty;
        $values['price'] = $this->price;
        $values['discount'] = $this->discount;
        $values['order'] = $this->order;
        $values['exemption_reason'] = $this->exemption_reason;
        $values['taxes'] = $this->taxes;
        $values['warehouse_id'] = $this->warehouse_id;

        return $values;
    }
}