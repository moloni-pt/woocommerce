<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Exceptions\APIException;
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
        add_action('woocommerce_update_product', [$this, 'productCreateUpdate'], 10, 2);
        add_action('woocommerce_update_product_variation', [$this, 'productCreateUpdate'], 10, 2);
    }

    /**
     * Public hook
     *
     * @param int $productId
     * @param WC_Product|null $product Product object passed by the hook (already holds the saved values)
     *
     * @return void
     */
    public function productCreateUpdate($productId, $product = null)
    {
        if (!$this->shouldRunHook($productId)) {
            return;
        }

        try {
            /**
             * Use the object provided by the hook instead of re-fetching it.
             * During WooCommerce's scheduled sales cron (woocommerce_scheduled_sales),
             * a fresh wc_get_product() returns the pre-transition price from the runtime
             * cache, causing the old (sale) price to be synced to Moloni. The object
             * passed by the hook always holds the just-saved values.
             *
             * Exception: variable products. Their price is aggregated from the
             * variations, and the object passed during the variation-sync cascade can
             * still hold the stale (pre-transition) price range. For those, the parent
             * price is already recalculated in the database, so re-fetching returns the
             * correct value.
             */
            if (!($product instanceof WC_Product) || $product->is_type('variable')) {
                $product = wc_get_product($productId);
            }

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
