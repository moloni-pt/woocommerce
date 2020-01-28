<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Controllers\Product;
use Moloni\Error;
use Moloni\Log;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Start;

class productupdate
{

    public $parent;

    /**
     * @param plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_update_product', [$this, 'productcreateupdate']);
    }

    public function productcreateupdate($productid)
    {
        try {
            $product = wc_get_product($productid);
            try {
                if ($product->get_status() !== 'draft' && start::login(true)) {
                    /** @noinspection NestedPositiveIfStatementsInspection */
                    if (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC) {
                        $productObj = new product($product);
                        if (!$productObj->loadbyreference()) {
                            $productObj->create();

                            if ($productObj->product_id > 0) {
                                notice::addmessagesuccess(__('o artigo foi criado no moloni'));
                            }
                        } else {
                            $productObj->update();
                            notice::addmessageinfo(__('o artigo já existe no moloni'));
                        }
                    }
                }
            } catch (error $error) {
                notice::addmessagecustom(htmlentities($error->geterror()));
            }
        } catch (exception $ex) {
            log::write('fatal error: ' . $ex->getmessage());
        }
    }
}
