<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Exceptions\APIExeption;
use Moloni\Exceptions\GenericException;
use Moloni\Storage;
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

    private $moloniProduct = [];

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
    public $taxes;
    public $visibility_id = 1;
    public $fiscalZone;

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
     *
     * @throws APIExeption
     */
    public function loadByReference()
    {
        $this->setReference();

        $searchProduct = Curl::simple('products/getByReference', ['reference' => $this->reference, 'with_invisible' => true, 'exact' => 1]);

        if (!empty($searchProduct) && isset($searchProduct[0]['product_id'])) {
            $this->moloniProduct = $searchProduct[0];

            $this->product_id = $this->moloniProduct['product_id'];
            $this->name = $this->moloniProduct['name'];
            $this->summary = $this->moloniProduct['summary'];
            $this->category_id = $this->moloniProduct['category_id'];
            $this->has_stock = $this->moloniProduct['has_stock'];
            $this->stock = $this->moloniProduct['stock'];
            $this->price = $this->moloniProduct['price'];
            $this->child_products = $this->moloniProduct['child_products'];
            $this->composition_type = $this->moloniProduct['composition_type'];
            $this->taxes = $this->moloniProduct['taxes'];
            $this->visibility_id = $this->moloniProduct['visibility_id'];

            return $this;
        }

        return false;
    }

    /**
     * Create a product based on a WooCommerce Product
     * @return Product
     *
     * @throws APIExeption
     * @throws GenericException
     */
    public function create()
    {
        $this->setProduct();

        $props = $this->mapPropsToValues();
        $props = apply_filters('moloni_before_moloni_product_insert', $props);

        $insert = Curl::simple('products/insert', $props);

        if (isset($insert['product_id'])) {
            $this->product_id = $insert['product_id'];

            Storage::$LOGGER->info(
                str_replace('{0}', $this->reference, __('Artigo Moloni criado com sucesso ({0})')),
                [
                    'product_id' => $this->product_id,
                    'props' => $props
                ]
            );

            return $this;
        }

        throw new GenericException(__('Erro ao inserir o artigo ') . $this->name);
    }

    /**
     * Create a product based on a WooCommerce Product
     *
     * @return $this
     *
     * @throws APIExeption
     * @throws GenericException
     */
    public function update(): Product
    {
        $this->setProduct();

        $props = $this->mapPropsToValues();
        $props = apply_filters('moloni_before_moloni_product_update', $props);

        if (!$this->needsToUpdateProduct($props)) {
            return $this;
        }

        $update = Curl::simple('products/update', $props);

        if (isset($update['product_id'])) {
            $this->product_id = $update['product_id'];

            Storage::$LOGGER->info(
                str_replace('{0}', $this->reference, __('Artigo Moloni atualizado com sucesso ({0})')),
                [
                    'product_id' => $this->product_id,
                    'props' => $props
                ]
            );

            return $this;
        }

        throw new GenericException(__('Erro ao atualizar o artigo ') . $this->name);
    }

    //          Gets          //

    /**
     * @return bool|int
     */
    public function getProductId()
    {
        return $this->product_id ?: false;
    }

    public function getDefaultTax()
    {
        $moloniTax = Tools::getTaxFromRate(-1);

        $tax = [];
        $tax['tax_id'] = $moloniTax['tax_id'];
        $tax['value'] = $moloniTax['value'];
        $tax['order'] = 1;
        $tax['cumulative'] = '0';

        if ((float)$moloniTax['value'] > 0) {
            return $tax;
        }

        return [];
    }

    //          Privates          //

    /**
     * @throws APIExeption
     * @throws GenericException
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

    //          Sets          //

    /**
     * @return $this
     */
    private function setReference()
    {
        $this->reference = $this->product->get_sku();

        if (empty($this->reference)) {
            $this->reference = Tools::createReferenceFromString($this->product->get_name(), $this->product->get_id());
        }

        $this->reference = mb_substr($this->reference, 0, 30);

        return $this;
    }

    /**
     * @return Product
     *
     * @throws APIExeption
     * @throws GenericException
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
            return $this;
        }

        $metaBarcode = $this->product->get_meta('_ywbc_barcode_display_value', true);
        if (!empty($metaBarcode)) {
            $this->ean = $metaBarcode;
            return $this;
        }

        return $this;
    }

    /**
     * Set measurement unit
     *
     * @throws GenericException
     */
    private function setUnitId(): Product
    {
        if (defined('MEASURE_UNIT')) {
            $this->unit_id = MEASURE_UNIT;
        } else {
            throw new GenericException(__('Unidade de medida não definida!'));
        }

        return $this;
    }

    /**
     * Sets the taxes of a product or its exemption reason
     *
     * @throws APIExeption
     */
    private function setTaxes()
    {
        $hasIVA = false;
        $this->taxes = [];

        if ($this->product->get_tax_status() === 'taxable') {
            // Get taxes based on a tax class of a product
            // If the tax class is empty it means the products uses the shop default
            $productTaxes = $this->product->get_tax_class();
            $taxRates = WC_Tax::get_base_tax_rates($productTaxes);

            if (empty($this->fiscalZone)) {
                $company = Curl::simple('companies/getOne', []);
                $this->fiscalZone = strtoupper($company['country']['iso_3166_1']);
            }

            foreach ($taxRates as $order => $taxRate) {
                $moloniTax = Tools::getTaxFromRate((float)$taxRate['rate'], $this->fiscalZone);

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

                    if ((int)$moloniTax['saft_type'] === 1) {
                        $hasIVA = true;
                    }
                }
            }
        }

        if (!$hasIVA) {
            if (defined('EXEMPTION_REASON') && EXEMPTION_REASON !== '') {
                $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
            } else {
                $this->taxes[] = $this->getDefaultTax();
            }
        }

        return $this;
    }

    //          Auxiliary          //

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

        if (empty($this->product_id)) {
            $values['at_product_category'] = $this->at_product_category;
        }

        $values['price'] = $this->price;
        $values['unit_id'] = $this->unit_id;
        $values['has_stock'] = $this->has_stock;
        $values['stock'] = $this->stock;
        $values['exemption_reason'] = $this->exemption_reason;
        $values['taxes'] = $this->taxes;
        $values['visibility_id'] = $this->visibility_id;

        return $values;
    }

    /**
     * Check if any attribute is outdated
     *
     * @param array $props
     *
     * @return true
     */
    private function needsToUpdateProduct(array $props): bool
    {
        if (empty($this->moloniProduct)) {
            return true;
        }

        if (
            (int)$props['category_id'] !== (int)$this->moloniProduct['category_id'] ||
            (int)$props['unit_id'] !== (int)$this->moloniProduct['unit_id'] ||
            (int)$props['visibility_id'] !== (int)$this->moloniProduct['visibility_id'] ||
            round($props['price'], 5) !== round($this->moloniProduct['price'], 5) ||
            ($props['name'] ?? '') !== ($this->moloniProduct['name'] ?? '') ||
            ($props['summary'] ?? '') !== ($this->moloniProduct['summary'] ?? '') ||
            ($props['ean'] ?? '') !== ($this->moloniProduct['ean'] ?? '') ||
            ($props['exemption_reason'] ?? '') !== ($this->moloniProduct['exemption_reason'] ?? '')
        ) {
            return true;
        }

        $propsTaxCount = count($props['taxes'] ?? []);
        $newTaxCount = count($this->taxes ?? []);

        if ($propsTaxCount !== $newTaxCount) {
            return true;
        }

        if ($propsTaxCount > 0 && $newTaxCount > 0) {
            foreach ($props['taxes'] as $propTax) {
                foreach ($this->taxes as $newTax) {
                    if ((int)$newTax['tax_id'] === (int)$propTax['tax_id']) {
                        continue 2;
                    }
                }

                return true;
            }
        }

        return false;
    }
}
