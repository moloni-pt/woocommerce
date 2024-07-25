<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Exceptions\APIExeption;
use Moloni\Exceptions\Core\MoloniException;
use Moloni\Exceptions\GenericException;
use Moloni\Storage;
use WC_Product;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Start;
use Moloni\Helpers\SyncLogs;
use Moloni\Enums\SyncLogsType;
use Moloni\Controllers\Product;

class ProductUpdate
{
    /**
     * Main class
     *
     * @var Plugin
     */
    public $parent;

    /**
     * Constructor
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_update_product', [$this, 'productCreateUpdate']);
        add_action('woocommerce_update_product_variation', [$this, 'productCreateUpdate']);
    }

    /**
     * Public hook
     *
     * @param $productId
     *
     * @return void
     */
    public function productCreateUpdate($productId)
    {
        if (!$this->shouldRunHook($productId)) {
            return;
        }

        try {
            $product = wc_get_product($productId);

            try {
                if ($this->shouldProcessProduct($product)) {
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
            } catch (MoloniException $error) {
                Notice::addMessageCustom(htmlentities($error->geterror()));
            }
        } catch (exception $ex) {
            Storage::$LOGGER->critical(__('Erro fatal'), [
                'action' => 'automatic:product:save',
                'exception' => $ex->getMessage()
            ]);
        }
    }

    //          Privates          //

    /**
     * Update/insert action
     *
     * @param WC_Product $product
     *
     * @throws APIException
     * @throws GenericException
     */
    private function updateOrInsertProduct(WC_Product $product): void
    {
        $productObj = new product($product);

        if (!$productObj->loadbyreference()) {
            if ($this->shouldInsertProduct()) {
                $productObj->create();

                if ($productObj->product_id > 0) {
                    Notice::addMessageSuccess(__('O artigo foi criado no moloni'));
                }
            }
        } else if ($this->shouldUpdateProduct()) {
            $productObj->update();
            Notice::addMessageCustom(__('O artigo já existe no moloni'));
        }
    }

    //          Auxiliary          //

    /**
     * Check if hook should be run
     *
     * @param int $productId
     *
     * @return bool
     */
    private function shouldRunHook(int $productId): bool
    {
        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT, $productId)) {
            return false;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT, $productId);

        return true;
    }

    /**
     * Check if product should be processed
     *
     * @param WC_Product $product
     *
     * @return bool
     */
    private function shouldProcessProduct(WC_Product $product): bool
    {
        if (empty($product) || $product->get_status() === 'draft') {
            return false;
        }

        return start::login(true);
    }

    /**
     * Check if product should be created
     *
     * @return bool
     */
    private function shouldInsertProduct(): bool
    {
        return (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC);
    }

    /**
     * Check if product should be updated
     *
     * @return bool
     */
    private function shouldUpdateProduct(): bool
    {
        return (defined('MOLONI_PRODUCT_SYNC_UPDATE') && MOLONI_PRODUCT_SYNC_UPDATE);
    }
}
