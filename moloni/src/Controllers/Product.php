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

    /** @var WC_Product|false */
    private $productParent = false;

    public $product_id;
    public $category_id;
    private $type;
    public $reference;
    public $name;
    public $summary = '';
    private $ean = '';
    public $price;
    private $unit_id;
    public $has_stock;
    public $stock;
    private $at_product_category = 'M';
    private $exemption_reason;
    private $taxes;

    public $composition_type = 0;
    /** @var false|array */
    public $child_products = false;

    /**
     * Product constructor.
     * @param WC_Product $product
     */
    public function __construct($product)
    {
        $this->product = $product;

        $parentId = $this->product->get_parent_id();
        if ($parentId > 0) {
            $this->productParent = wc_get_product($parentId);
        }
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
            $this->name = $product['name'];
            $this->summary = $product['summary'];
            $this->category_id = $product['category_id'];
            $this->has_stock = $product['has_stock'];
            $this->stock = $product['stock'];
            $this->price = $product['price'];
            $this->child_products = $product['child_products'];
            $this->composition_type = $product['composition_type'];
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
        $this->setProduct();

        $insert = Curl::simple('products/insert', $this->mapPropsToValues());
        if (isset($insert['product_id'])) {
            $this->product_id = $insert['product_id'];
            return $this;
        }

        throw new Error(__('Erro ao inserir o artigo ') . $this->name);
    }

    /**
     * Create a product based on a WooCommerce Product
     * @return $this
     * @throws Error
     */
    public function update()
    {
        $this->setProduct();

        $update = Curl::simple('products/update', $this->mapPropsToValues());

        if (isset($update['product_id'])) {
            $this->product_id = $update['product_id'];
            return $this;
        }

        throw new Error(__('Erro ao atualizar o artigo ') . $this->name);
    }

    /**
     * @throws Error
     */
    private function setProduct()
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
        $categories = $this->product->get_category_ids();

        if (empty($categories) && $this->productParent) {
            $categories = $this->productParent->get_category_ids();
        }

        // Get the deepest category from all the trees
        if (!empty($categories) && is_array($categories)) {
            $categoryTree = [];

            foreach ($categories as $category) {
                $parents = get_ancestors($category, 'product_cat');
                $parents = array_reverse($parents);
                $parents[] = $category;

                if (is_array($parents) && count($parents) > count($categoryTree)) {
                    $categoryTree = $parents;
                }
            }

            $this->category_id = 0;
            foreach ($categoryTree as $categoryId) {
                $category = get_term_by('id', $categoryId, 'product_cat');
                if (!empty($category->name)) {
                    $categoryObj = new ProductCategory($category->name, $this->category_id);

                    if (!$categoryObj->loadByName()) {
                        $categoryObj->create();
                    }

                    $this->category_id = $categoryObj->category_id;
                }
            }
        }

        if ((int)$this->category_id === 0) {
            $categoryObj = new ProductCategory('Loja Online', 0);

            if (!$categoryObj->loadByName()) {
                $categoryObj->create();
            }

            $this->category_id = $categoryObj->category_id;
        }

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
            $this->has_stock = $this->product->managing_stock() ? 1 : 0;
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
        $this->name = strip_tags($this->product->get_name());
        return $this;
    }

    /**
     * Set the price of the product
     * @return $this
     */
    private function setPrice()
    {
        $this->price = (float)wc_get_price_excluding_tax($this->product);

        if ((float)$this->price === 0 && $this->productParent) {
            $this->price = (float)wc_get_price_excluding_tax($this->productParent);
        }

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
        $hasIVA = false;

        if ($this->product->get_tax_status() === 'taxable') {
            // Get taxes based on a tax class of a product
            // If the tax class is empty it means the products uses the shop default
            $productTaxes = $this->product->get_tax_class();
            $taxRates = WC_Tax::get_base_tax_rates($productTaxes);

            foreach ($taxRates as $order => $taxRate) {
                $moloniTax = Tools::getTaxFromRate((float)$taxRate['rate']);

                if (!$moloniTax) {
                    continue;
                }

                $tax = [];
                $tax['tax_id'] = $moloniTax['tax_id'];
                $tax['value'] = $taxRate['rate'];
                $tax['order'] = $order;
                $tax['cumulative'] = '0';

                if ((float)$taxRate['rate'] > 0) {
                    $this->taxes[] = $tax;
                }

                if ((int)$moloniTax['saft_type'] === 1) {
                    $hasIVA = true;
                }
            }
        }

        if (!$hasIVA) {
            if (!defined('EXEMPTION_REASON') || empty(EXEMPTION_REASON)) {
                /** Get the default tax from Moloni Account*/
                $moloniTax = Tools::getTaxFromRate(-1);

                $tax = [];
                $tax['tax_id'] = $moloniTax['tax_id'];
                $tax['value'] = $moloniTax['value'];
                $tax['order'] = 1;
                $tax['cumulative'] = '0';

                if ((float)$moloniTax['value'] > 0) {
                    $this->taxes[] = $tax;
                }
            } else {
                $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
            }
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

        if (!empty($this->ean)) {
            /** EAN is created from an external plugin so avoid to update it */
            $values['ean'] = $this->ean;
        }

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