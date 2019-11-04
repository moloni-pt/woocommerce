<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Controllers\Product;
use Moloni\Error;
use Moloni\Log;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Start;

class ProductUpdate
{

    public $parent;

    /**
     * @param Plugin $parent
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
                if ($product->get_status() !== 'draft' && Start::login()) {
                    if (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC) {
                        $productObj = new Product($product);
                        if (!$productObj->loadByReference()) {
                            $productObj->create();

                            if ($productObj->product_id > 0) {
                                Notice::addMessageSuccess(__("O artigo foi criado no Moloni"));
                            }
                        } else {
                            Notice::addMessageInfo(__("O artigo já existe no Moloni"));
                        }
                    }
                }
            } catch (Error $error) {
                Notice::addMessageCustom(htmlentities($error->getError()));
            }
        } catch (Exception $ex) {
            Log::write("Fatal error: " . $ex->getMessage());
        }
    }
}
