<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Controllers\Product;
use Moloni\Error;
use Moloni\Log;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Start;
use WC_Product;

class ProductUpdate
{

    public $parent;

    /**
     * @param plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_update_product', [$this, 'productCreateUpdate']);
    }

    public function productCreateUpdate($productId)
    {
        try {
            $product = wc_get_product($productId);
            try {
                if ($product->get_status() !== 'draft' && start::login(true)) {
                    if ($this->shouldInsertProduct() || $this->shouldUpdateProduct()) {
                        $this->updateOrInsertProduct($product);

                        $childProducts = $product->get_children();
                        if (!empty($childProducts) && is_array($childProducts)) {
                            foreach ($childProducts as $childProduct) {
                                $product = wc_get_product($childProduct);
                                $this->updateOrInsertProduct($product);
                            }
                        }
                    }
                }
            } catch (Error $error) {
                Notice::addMessageCustom(htmlentities($error->geterror()));
            }
        } catch (exception $ex) {
            log::write('Fatal error: ' . $ex->getmessage());
        }
    }

    /**
     * @param WC_Product $product
     * @throws Error
     */
    private function updateOrInsertProduct($product)
    {
        $productObj = new product($product);
        if (!$productObj->loadbyreference()) {
            $productObj->create();

            if ($productObj->product_id > 0) {
                Notice::addMessageSuccess(__('O artigo foi criado no moloni'));
            }
        } else if ((defined('MOLONI_PRODUCT_SYNC_UPDATE') && MOLONI_PRODUCT_SYNC_UPDATE)) {
            $productObj->update();
            Notice::addMessageCustom(__('O artigo já existe no moloni'));
        }
    }

    private function shouldUpdateProduct() {
        return (defined('MOLONI_PRODUCT_SYNC_UPDATE') && MOLONI_PRODUCT_SYNC_UPDATE);
    }

    private function shouldInsertProduct()
    {
        return (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC);
    }
}
