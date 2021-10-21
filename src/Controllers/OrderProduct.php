<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Tools;
use WC_Order;
use WC_Order_Item_Product;
use WC_Tax;

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

    /** @var Product */
    private $moloniProduct;

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

    /** @var bool */
    private $hasIVA = false;

    /** @var bool */
    private $fiscalZone;

    /** @var int */
    public $composition_type = 0;

    /** @var false|array */
    public $child_products = false;

    /**
     * OrderProduct constructor.
     * @param WC_Order_Item_Product $product
     * @param WC_Order $wcOrder
     * @param int $order
     */
    public function __construct($product, $wcOrder, $order = 0, $fiscalZone = 'PT')
    {
        $this->product = $product;
        $this->wc_order = $wcOrder;
        $this->order = $order;
        $this->fiscalZone = $fiscalZone;
    }

    /**
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this
            ->setName()
            ->setQty()
            ->setPrice()
            ->setSummary()
            ->setProductId()
            ->setDiscount()
            ->setTaxes()
            ->setWarehouse()
            ->setChildProducts();

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
        $summary = apply_filters('moloni_before_order_item_setSummary', $summary, $this->product);

        if ($summary) {
            $this->summary = $summary;
        } else {
            $this->summary .= $this->getSummaryVariationAttributes();

            if (!empty($this->summary)) {
                $this->summary .= "\n";
            }

            $this->summary .= $this->getSummaryExtraProductOptions();
        }

        $this->summary = apply_filters('moloni_after_order_item_setSummary', $summary, $this->product);

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
        $this->price = (float)$this->product->get_subtotal() / $this->qty;

        $refundedValue = $this->wc_order->get_total_refunded_for_item($this->product->get_id());

        if ($refundedValue !== 0) {
            $refundedValue /= $this->qty;

            $this->price -= $refundedValue;
        }

        if ($this->price < 0) {
            $this->price = 0;
        }

        return $this;
    }

    /**
     * @return OrderProduct
     */
    public function setQty()
    {
        $this->qty = (float)$this->product->get_quantity();

        $refundedQty = absint($this->wc_order->get_qty_refunded_for_item($this->product->get_id()));

        if ($refundedQty !== 0) {
            $this->qty -= $refundedQty;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setProductId()
    {
        $this->moloniProduct = new Product($this->product->get_product());

        if (!$this->moloniProduct->loadByReference()) {
            $this->moloniProduct->fiscalZone = $this->fiscalZone;
            $this->moloniProduct->create();
        } elseif (defined('USE_MOLONI_PRODUCT_DETAILS') && USE_MOLONI_PRODUCT_DETAILS) {
            $this->name = $this->moloniProduct->name;
            $this->summary = $this->moloniProduct->summary;
        }

        if ($this->moloniProduct->visibility_id === 0) {
            throw new Error('Produto com referência ' . $this->moloniProduct->reference . ' tem de estar ativo.');
        }

        $this->composition_type = $this->moloniProduct->composition_type;
        $this->product_id = $this->moloniProduct->getProductId();

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
        $taxes = $this->product->get_taxes();
        foreach ($taxes['subtotal'] as $taxId => $value) {
            if (!empty($value)) {
                $taxRate = preg_replace('/[^0-9.]/', '', WC_Tax::get_rate_percent($taxId));

                if ((float)$taxRate > 0) {
                    $this->taxes[] = $this->setTax($taxRate);
                }
            }
        }

        if (!$this->hasIVA) {
            $billingCountryCode = $this->wc_order->get_billing_country();

            if (defined('EXEMPTION_REASON_EXTRA_COMMUNITY') && EXEMPTION_REASON_EXTRA_COMMUNITY !== '' &&
                !empty($billingCountryCode) && !isset(Tools::$europeanCountryCodes[$billingCountryCode])) {
                $this->exemption_reason = EXEMPTION_REASON_EXTRA_COMMUNITY;
            } elseif (defined('EXEMPTION_REASON') && EXEMPTION_REASON !== '') {
                $this->exemption_reason = EXEMPTION_REASON;
            } else {
                $this->taxes[] = $this->moloniProduct->getDefaultTax();
            }
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
        $moloniTax = Tools::getTaxFromRate((float)$taxRate, $this->fiscalZone);

        $tax = [];
        $tax['tax_id'] = $moloniTax['tax_id'];
        $tax['value'] = $taxRate;
        $tax['order'] = is_array($this->taxes) ? count($this->taxes) : 0;
        $tax['cumulative'] = 0;

        if ((int)$moloniTax['saft_type'] === 1) {
            $this->hasIVA = true;
        }

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
     * @return $this
     * @throws Error
     */
    private function setChildProducts()
    {
        if ($this->composition_type === 1 && is_array($this->moloniProduct->child_products) && !empty($this->moloniProduct->child_products)) {
            if ($this->moloniProduct->price > 0) {
                $priceChangePercent = $this->price / $this->moloniProduct->price;
                $this->price = 0;

                foreach ($this->moloniProduct->child_products as $index => $childProduct) {
                    $moloniChildProduct = Curl::simple('products/getOne', ['product_id' => $childProduct['product_child_id']]);

                    $this->child_products[$index] = $moloniChildProduct;
                    $this->child_products[$index]['discount'] = (float)$this->discount > 0 ? $this->discount : 0;
                    $this->child_products[$index]['price'] = $childProduct['price'] * $priceChangePercent;
                    $this->child_products[$index]['qty'] = $childProduct['qty'] * $this->qty;

                    $this->price += (($childProduct['price'] * $priceChangePercent) * $childProduct['qty']);

                    //If billing country is not PT, change child products taxes to match order taxes
                    if ($this->wc_order->get_billing_country() !== 'PT') {
                        if (!empty($this->exemption_reason)) {
                            //Delete moloni taxes data
                            unset($this->child_products[$index]['taxes']);

                            //Keep this order defined exemption reason
                            $this->child_products[$index]['exemption_reason'] = $this->exemption_reason;
                        } else {
                            //Delete moloni exemption reason data
                            unset($this->child_products[$index]['exemption_reason']);

                            //Keep this order defined taxes
                            $this->child_products[$index]['taxes'] = $this->taxes;
                        }
                    } else {
                        //If billing country is PT, use Moloni defined taxes and/or exemption reason
                        if (empty($this->taxes)) {
                            unset($this->child_products[$index]['taxes']);

                            $this->child_products[$index]['exemption_reason'] = $this->exemption_reason;
                        }
                    }
                }
            }
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
        $values['child_products'] = $this->child_products;

        return $values;
    }
}