<?php

namespace Moloni\Services\WcProducts;

use WC_Tax;
use WC_Product;
use Moloni\Curl;
use Moloni\Storage;
use Moloni\Enums\Boolean;
use Moloni\Enums\TaxType;
use Moloni\Enums\SaftType;
use Moloni\Helpers\MoloniProduct;
use Moloni\Exceptions\APIExeption;


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

            return;
        }

        $this->wcProduct->set_tax_status('taxable');

        if ($this->wcProduct->exists()) {
            return;
        }

        if (count($this->moloniProduct['taxes']) > 1) {
            return;
        }

        $moloniTax = $this->moloniProduct['taxes'][0]['tax'] ?? [];

        if (empty($moloniTax)) {
            return;
        }

        if (
            (int)$moloniTax['saft_type'] !== SaftType::IVA ||
            (int)$moloniTax['type'] !== TaxType::PERCENTAGE
        ) {
            return;
        }

        $taxClasses = wc_get_product_tax_class_options() ?? [];

        if (empty($taxClasses)) {
            return;
        }

        foreach ($taxClasses as $taxClass => $taxClassLabel) {
            $taxRates = WC_Tax::find_rates([
                'country' => $moloniTax['fiscal_zone'],
                'tax_class' => $taxClass
            ]);

            foreach ($taxRates as $taxRate) {
                if ((int)($taxRate['rate'] * 100000) !== (int)($moloniTax['value'] * 100000)) {
                    continue;
                }

                $this->wcProduct->set_tax_class($taxClass);
                return;
            }
        }
    }

    private function setCategories()
    {
        try {
            $moloniCategoryTree = Curl::simple('products/getCategoryTree', [
                'product_id' => $this->moloniProduct['product_id'],
                'with_invisible' => true
            ]);
        } catch (APIException $e) {
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
