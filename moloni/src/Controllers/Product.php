<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Tools;
use WC_Product;
use WC_Tax;

/**
 * Class Product
 * @package Moloni\Controllers
 */
class Product
{

    /** @var WC_Product */
    private $product;

    public $product_id;
    public $category_id;
    private $type;
    public $reference;
    public $name;
    private $summary = '';
    private $ean = '';
    public $price;
    private $unit_id;
    public $has_stock;
    public $stock;
    private $at_product_category = 'M';
    private $exemption_reason;
    private $taxes;

    /**
     * Product constructor.
     * @param WC_Product $product
     */
    public function __construct($product)
    {
        $this->product = $product;
    }

    /**
     * Loads a product
     * @throws Error
     */
    public function loadByReference()
    {
        $this->setReference();

        $searchProduct = Curl::simple('products/getByReference', ['reference' => $this->reference, 'exact' => 1]);
        if (!empty($searchProduct) && isset($searchProduct[0]['product_id'])) {
            $product = $searchProduct[0];
            $this->product_id = $product['product_id'];
            $this->category_id = $product['category_id'];
            $this->has_stock = $product['has_stock'];
            $this->stock = $product['stock'];
            $this->price = $product['price'];
            return $this;
        }

        return false;
    }


    /**
     * Create a product based on a WooCommerce Product
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this
            ->setReference()
            ->setCategory()
            ->setType()
            ->setName()
            ->setPrice()
            ->setEan()
            ->setUnitId()
            ->setTaxes();

        $insert = Curl::simple('products/insert', $this->mapPropsToValues());
        if (isset($insert['product_id'])) {
            $this->product_id = $insert['product_id'];
            return $this;
        }

        throw new Error(__('Erro ao inserir o artigo ') . $this->name);
    }

    /**
     * @return bool|int
     */
    public function getProductId()
    {
        return $this->product_id ?: false;
    }

    /**
     * @return $this
     */
    private function setReference()
    {
        $this->reference = $this->product->get_sku();

        if (empty($this->reference)) {
            $this->reference = Tools::createReferenceFromString($this->product->get_name());
        }

        return $this;
    }

    /**
     * @throws Error
     */
    private function setCategory()
    {
        $categoryName = 'Loja Online';

        $categories = $this->product->get_category_ids();
        if (!empty($categories) && (int)$categories[0] > 0) {
            $category = get_term_by('id', $categories[0], 'product_cat');
            if (!empty($category->name)) {
                $categoryName = $category->name;
            }
        }

        $categoryObj = new ProductCategory($categoryName);
        if (!$categoryObj->loadByName()) {
            $categoryObj->create();
        }

        $this->category_id = $categoryObj->category_id;

        return $this;
    }

    /**
     * Available types:
     * 1 Product
     * 2 Service
     * 3 Other
     * @return $this
     */
    private function setType()
    {
        // If the product is virtual or downloadable then its a service
        if ($this->product->is_virtual() || $this->product->is_downloadable()) {
            $this->type = 2;
            $this->has_stock = 0;
        } else {
            $this->type = 1;
            $this->has_stock = 1;
            $this->stock = (float)$this->product->get_stock_quantity();
        }

        return $this;
    }


    /**
     * Set the name of the product
     * @return $this
     */
    private function setName()
    {
        $this->name = $this->product->get_name();
        return $this;
    }

    /**
     * Set the price of the product
     * @return $this
     */
    private function setPrice()
    {
        $this->price = (float)$this->product->get_price();
        return $this;
    }

    /**
     * @return $this
     */
    private function setEan()
    {
        $metaBarcode = $this->product->get_meta('barcode', true);
        if (!empty($metaBarcode)) {
            $this->ean = $metaBarcode;
        }

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
            throw new Error(__('Unidade de medida não definida!'));
        }

        return $this;
    }

    /**
     * Sets the taxes of a product or its exemption reason
     * @return $this
     * @throws Error
     */
    private function setTaxes()
    {
        if ($this->product->get_tax_status() === 'taxable') {
            // Get taxes based on a tax class of a product
            // If the tax class is empty it means the products uses the shop default
            $productTaxes = $this->product->get_tax_class();
            $taxRates = WC_Tax::get_base_tax_rates($productTaxes);

            foreach ($taxRates as $order => $taxRate) {
                $tax = [];
                $tax['tax_id'] = Tools::getTaxIdFromRate((float)$taxRate['rate']);
                $tax['value'] = $taxRate['rate'];
                $tax['order'] = $order;
                $tax['cumulative'] = '0';

                $this->taxes[] = $tax;
            }
        }

        if (empty($this->taxes) || (float)$this->taxes[0]['value'] === 0) {
            $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
        }

        return $this;
    }

    /**
     * Map this object properties to an array to insert/update a moloni document
     * @return array
     */
    private function mapPropsToValues()
    {
        $values = [];

        $values['product_id'] = $this->product_id;
        $values['category_id'] = $this->category_id;
        $values['type'] = $this->type;
        $values['reference'] = $this->reference;
        $values['name'] = $this->name;
        $values['summary'] = $this->summary;
        $values['ean'] = $this->ean;
        $values['price'] = $this->price;
        $values['unit_id'] = $this->unit_id;
        $values['has_stock'] = $this->has_stock;
        $values['stock'] = $this->stock;
        $values['at_product_category'] = $this->at_product_category;
        $values['exemption_reason'] = $this->exemption_reason;
        $values['taxes'] = $this->taxes;

        return $values;
    }
}