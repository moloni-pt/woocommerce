<?php

namespace Moloni\Services\WcProducts;

use Moloni\Curl;
use Moloni\Exceptions\APIExeption;
use Moloni\Helpers\MoloniProduct;
use WC_Product;
use Moloni\Storage;
use Moloni\Enums\Boolean;

class CreateProduct extends ImportService
{
    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = new WC_Product();
    }

    //            Public's            //

    public function run()
    {
        $this->setName();
        $this->setReference();
        $this->setPrice();
        $this->setTaxes();
        $this->setCategories();
        $this->setDescription();
        $this->setVisibility();
        $this->setStock();
        $this->setEan();

        $this->wcProduct->save();
    }

    public function saveLog()
    {
        $msg = str_replace('{0}', $this->wcProduct->get_sku(), __('Produto criado no WooCommerce ({0})'));

        Storage::$LOGGER->info($msg, [
            'tag' => 'service:wcproduct:create',
            'ml_id' => $this->moloniProduct['product_id'],
            'ml_reference' => $this->moloniProduct['reference'],
            'wc_id' => $this->wcProduct->get_id(),
        ]);
    }

    //            Sets            //

    private function setName()
    {
        $this->wcProduct->set_name($this->moloniProduct['name'] ?? '');
    }

    private function setReference()
    {
        $this->wcProduct->set_sku($this->moloniProduct['reference'] ?? '');
    }

    private function setPrice()
    {
        $price = $this->moloniProduct['price'];

        if (wc_prices_include_tax() && !empty($this->moloniProduct['taxes'])) {
            foreach ($this->moloniProduct['taxes'] as $tax) {
                $price += (float)$tax['value'];
            }
        }

        $this->wcProduct->set_regular_price($price);
    }

    private function setTaxes()
    {
        if (empty($this->moloniProduct['taxes'])) {
            $this->wcProduct->set_tax_status('none');
        } else {
            $this->wcProduct->set_tax_status('taxable');
        }
    }

    private function setCategories()
    {
        try {
            $moloniCategoryTree = Curl::simple('products/getCategoryTree', [
                'product_id' => $this->moloniProduct['product_id'],
                'with_invisible' => true
            ]);
        } catch (APIExeption $e) {
            $moloniCategoryTree = [];
        }

        $categoriesIds = [];

        if (!empty($moloniCategoryTree)) {
            $parentId = 0;

            foreach ($moloniCategoryTree as $moloniCategory) {
                $name = $moloniCategory['name'];
                $existingTerm = term_exists($name, 'product_cat', $parentId);

                if (!$existingTerm) {
                    $newTerm = wp_insert_term($name, 'product_cat', ['parent' => $parentId]);
                    $parentId = $newTerm['term_id'];

                    array_unshift($categoriesIds, $newTerm['term_id']);
                } else {
                    $parentId = $existingTerm['term_id'];

                    array_unshift($categoriesIds, $existingTerm['term_id']);
                }
            }
        }

        if (!empty($categoriesIds)) {
            $this->wcProduct->set_category_ids($categoriesIds);
        }
    }

    private function setStock()
    {
        $hasStock = (bool)$this->moloniProduct['has_stock'];

        $this->wcProduct->set_manage_stock($hasStock);

        if ($hasStock) {
            $stock = MoloniProduct::parseMoloniStock(
                $this->moloniProduct,
                defined('MOLONI_STOCK_SYNC') ? (int)MOLONI_STOCK_SYNC : 1
            );

            $this->wcProduct->set_stock_quantity($stock);
            $this->wcProduct->set_low_stock_amount($this->moloniProduct['minimum_stock']);
        }
    }

    private function setDescription()
    {
        $this->wcProduct->set_short_description($this->moloniProduct['summary'] ?? '');
        $this->wcProduct->set_description($this->moloniProduct['notes'] ?? '');
    }

    private function setVisibility()
    {
        $this->wcProduct->set_catalog_visibility((int)$this->moloniProduct['visibility_id'] === Boolean::YES ? 'visible' : 'hidden');
    }

    private function setEan()
    {
        $this->wcProduct->add_meta_data('_barcode', $this->moloniProduct['ean']);
    }
}
