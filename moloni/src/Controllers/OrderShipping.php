<?php
/**
 *
 *   Plugin Name:  Moloni
 *   Plugin URI:   https://plugins.moloni.com/woocommerce
 *   Description:  Send your orders automatically to your Moloni invoice software
 *   Version:      0.0.1
 *   Author:       Moloni.com
 *   Author URI:   https://moloni.com
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Tools;

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

    /** @var \WC_Order */
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

    /**
     * OrderProduct constructor.
     * @param \WC_Order $order
     * @param int $index
     */
    public function __construct($order, $index = 0)
    {
        $this->order = $order;
        $this->index = $index;
    }

    /**
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this->qty = 1;
        $this->price = (float)$this->order->get_shipping_total();
        $this->name = $this->order->get_shipping_method();

        $this
            ->setReference()
            ->setDiscount()
            ->setTaxes()
            ->setProductId();

        return $this;
    }


    /**
     * @return $this
     */
    private function setReference()
    {
        $this->reference = "Portes";
        return $this;
    }

    /**
     * @throws Error
     * @return $this
     */
    private function setProductId()
    {

        $searchProduct = Curl::simple("products/getByReference", ["reference" => $this->reference, "exact" => 1]);
        if (!empty($searchProduct) && isset($searchProduct[0]['product_id'])) {
            $this->product_id = $searchProduct[0]['product_id'];
            return $this;
        }

        // Lets create the shipping product
        $this
            ->setCategory()
            ->setUnitId();

        $insert = Curl::simple("products/insert", $this->mapPropsToValues(true));
        if (isset($insert['product_id'])) {
            $this->product_id = $insert['product_id'];
            return $this;
        }

        throw new Error("Erro ao inserir portes de envio");
    }

    /**
     * @throws Error
     */
    private function setCategory()
    {
        $categoryName = "Loja Online";

        $categoryObj = new ProductCategory($categoryName);
        if (!$categoryObj->loadByName()) {
            $categoryObj->create();
        }

        $this->category_id = $categoryObj->category_id;

        return $this;
    }

    /**
     * @throws Error
     * @return $this
     */
    private function setUnitId()
    {
        if (defined("MEASURE_UNIT")) {
            $this->unit_id = MEASURE_UNIT;
        } else {
            throw new Error("Unidade de medida não definida!");
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
        $this->discount = $this->discount < 0 ? 0 : $this->discount > 100 ? 100 : $this->discount;

        return $this;
    }

    /**
     * Set the taxes of a product
     * @throws Error
     */
    private function setTaxes()
    {

        $taxRate = round(($this->order->get_shipping_tax() * 100) / $this->order->get_shipping_total());

        if ((float)$taxRate > 0) {
            $this->taxes[] = $this->setTax($taxRate);
        }

        if (empty($this->taxes)) {
            $this->exemption_reason = defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '';
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
        $tax['order'] = 0;
        $tax['cumulative'] = 0;

        return $tax;
    }

    /**
     * @param bool $toInsert
     * @return array
     */
    public function mapPropsToValues($toInsert = false)
    {
        $values = [];

        $values["product_id"] = $this->product_id;
        $values["name"] = $this->name;
        $values["summary"] = "";
        $values["qty"] = $this->qty;
        $values["price"] = $this->price;
        $values["discount"] = $this->discount;
        $values["order"] = $this->index;
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