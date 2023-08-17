<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Tools;
use WC_Order;

class OrderShipping
{

    /** @var int */
    public $product_id = 0;

    /** @var int */
    private $index;

    /** @var array */
    private $taxes = [];

    /** @var float */
    private $qty;

    /** @var float */
    private $price;

    /** @var string */
    private $exemption_reason;

    /** @var string */
    private $name;

    /** @var float */
    private $discount;

    /** @var WC_Order */
    private $order;

    /** @var string */
    private $reference;

    /** @var int */
    private $category_id;

    private $type = 2;
    private $summary = '';
    private $ean = '';
    private $unit_id;
    private $has_stock = 0;
    private $stock = 0;
    private $at_product_category = 'M';

    /** @var bool */
    private $hasIVA = false;

    /** @var bool */
    private $fiscalZone;

    /**
     * OrderProduct constructor.
     * @param WC_Order $order
     * @param int $index
     */
    public function __construct($order, $index = 0, $fiscalZone = 'PT')
    {
        $this->order = $order;
        $this->index = $index;
        $this->fiscalZone = $fiscalZone;
    }

    /**
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this
            ->setQuantity()
            ->setPrice()
            ->setReference()
            ->setDiscount()
            ->setTaxes()
            ->setName()
            ->setSummary()
            ->setProductId();

        return $this;
    }

    //            Sets            //

    private function setQuantity()
    {
        $this->qty = 1;

        return $this;
    }

    private function setPrice()
    {
        $price = (float)$this->order->get_shipping_total();

        $refundedValue = (float)$this->order->get_total_shipping_refunded();

        if ($refundedValue > 0) {
            $price -= $refundedValue;
        }

        if ($price < 0) {
            $price = 0;
        }

        $this->price = $price;

        return $this;
    }

    /**
     * @return $this
     */
    private function setReference()
    {
        $this->reference = 'Portes';
        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setProductId()
    {
        $searchProduct = Curl::simple('products/getByReference', ['reference' => $this->reference, 'with_invisible' => true, 'exact' => 1]);

        if (!empty($searchProduct) && isset($searchProduct[0]['product_id'])) {
            if ($searchProduct[0]['visibility_id'] === 1) {
                $this->product_id = $searchProduct[0]['product_id'];
            } else {
                throw new Error('Produto com referência ' . $this->reference . ' tem de estar ativo.');
            }

            return $this;
        }

        // Lets create the shipping product
        $this
            ->setCategory()
            ->setUnitId();

        $insert = Curl::simple('products/insert', $this->mapPropsToValues(true));
        if (isset($insert['product_id'])) {
            $this->product_id = $insert['product_id'];
            return $this;
        }

        throw new Error('Erro ao inserir portes de envio');
    }

    /**
     * @throws Error
     */
    private function setCategory()
    {
        $categoryName = 'Loja Online';

        $categoryObj = new ProductCategory($categoryName);
        if (!$categoryObj->loadByName()) {
            $categoryObj->create();
        }

        $this->category_id = $categoryObj->category_id;

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setUnitId()
    {
        if (defined('MEASURE_UNIT')) {
            $this->unit_id = MEASURE_UNIT;
        } else {
            throw new Error('Unidade de medida não definida!');
        }

        return $this;
    }

    /**
     * Set the discount in percentage
     * @return $this
     */
    private function setDiscount()
    {
        $this->discount = $this->price <= 0 ? 100 : 0;

        if ($this->discount < 0) {
            $this->discount = 0;
        } elseif ($this->discount > 100) {
            $this->discount = 100;
        }

        return $this;
    }

    /**
     * Set the taxes of a product
     * @throws Error
     */
    private function setTaxes()
    {
        $shippingTotal = 0;

        foreach ($this->order->get_shipping_methods() as $item_id => $item) {
            $taxes = $item->get_taxes();
            foreach ($taxes['total'] as $tax_rate_id => $tax) {
                $shippingTotal += (float)$tax;
            }
        }

        $taxRate = round(($shippingTotal * 100) / $this->order->get_shipping_total());

        if ((float)$taxRate > 0) {
            $this->taxes[] = $this->setTax($taxRate);
        }

        if (!$this->hasIVA) {
            $exemptionReason = '';

            if (isset(Tools::$europeanCountryCodes[$this->fiscalZone])) {
                $exemptionReason = defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '';
            } else {
                if (defined('EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY')) {
                    $exemptionReason = EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY;
                } elseif (defined('EXEMPTION_REASON_SHIPPING')) {
                    $exemptionReason = EXEMPTION_REASON_SHIPPING;
                }
            }

            $this->exemption_reason = $exemptionReason;
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
     * @param string $name
     * @return $this
     */
    public function setName($name = '')
    {
        $name = empty($name) ? $this->order->get_shipping_method() : $name;

        $this->name = apply_filters('moloni_after_order_shipping_setName', $name, $this->order);

        return $this;
    }

    /**
     * @param string $summary
     * @return $this
     */
    public function setSummary($summary = '')
    {
        $summary = empty($summary) ? '' : $summary;

        $this->summary = apply_filters('moloni_after_order_shipping_setSummary', $summary, $this->order);

        return $this;
    }

    //            Gets            //

    public function getPrice()
    {
        return $this->price ?? 0.0;
    }

    /**
     * @param bool $toInsert
     * @return array
     */
    public function mapPropsToValues($toInsert = false)
    {
        $values = [];

        $values['product_id'] = $this->product_id;
        $values['name'] = $this->name;
        $values['summary'] = '';
        $values['qty'] = $this->qty;
        $values['price'] = $this->price;
        $values['discount'] = $this->discount;
        $values['order'] = $this->index;
        $values['exemption_reason'] = $this->exemption_reason;
        $values['taxes'] = $this->taxes;

        if ($toInsert) {
            $values['reference'] = $this->reference;
            $values['type'] = $this->type;
            $values['stock'] = $this->stock;
            $values['has_stock'] = $this->has_stock;
            $values['at_product_category'] = $this->at_product_category;
            $values['summary'] = $this->summary;
            $values['ean'] = $this->ean;
            $values['unit_id'] = $this->unit_id;
            $values['category_id'] = $this->category_id;
        }

        return $values;
    }
}
