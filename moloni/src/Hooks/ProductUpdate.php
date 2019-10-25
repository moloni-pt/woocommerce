<?php

namespace Moloni\Hooks;

use Moloni\Controllers\Product;
use Moloni\Error;
use Moloni\Log;
use Moloni\Plugin;
use Moloni\Start;

class ProductUpdate
{

    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_update_product', [$this, 'productCreateUpdate']);
    }

    public function productCreateUpdate($productId)
    {
        Log::setFileName('ProductsCreateUpdate');
        try {
            $product = wc_get_product($productId);
            try {
                if ($product->get_status() !== 'draft' && Start::login()) {
                    if (defined('MOLONI_PRODUCTS_SYNC') && MOLONI_PRODUCTS_SYNC) {
                        $productObj = new Product($product);
                        if (!$productObj->loadByReference()) {
                            $productObj->create();
                        }
                    }
                }
            } catch (Error $error) {
                $error->showError();
            }
        } catch (\Exception $ex) {
            Log::write("Fatal error: " . $ex->getMessage());
        }
    }

}
