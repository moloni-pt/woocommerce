<?php

namespace Moloni\Services\WcProducts;

use Moloni\Exceptions\Stocks\StockLockedException;
use Moloni\Exceptions\Stocks\StockMatchingException;
use Moloni\Helpers\MoloniProduct;
use Moloni\Storage;
use WC_Product;

class UpdateProductStock extends ImportService
{
    private $locked = false;

    private $warehouseId = 0;

    private $wcStock = 0;
    private $moloniStock = 0;

    private $resultMessage = '';
    private $resultData = [];

    public function __construct(WC_Product $wcProduct, array $moloniProduct)
    {
        $this->wcProduct = $wcProduct;
        $this->moloniProduct = $moloniProduct;

        $this->init();
    }

    //            Public's            //

    /**
     * Service runner
     *
     * @throws StockLockedException
     * @throws StockMatchingException
     */
    public function run(): void
    {
        if ($this->locked) {
            throw new StockLockedException(__('Serviço foi bloqueado'));
        }

        if ($this->wcStock === $this->moloniStock) {
            $message = sprintf(
                __('Stock já se encontra correto no WooCommerce (%d|%d) (%s)'),
                $this->wcStock,
                $this->moloniStock,
                $this->moloniProduct['reference']
            );

            throw new StockMatchingException($message);
        }

        wc_update_product_stock($this->wcProduct, $this->moloniStock);

        $this->resultMessage = sprintf(
            __('Stock atualizado no WooCommerce (antes: %s | depois: %s) (%s)'),
            $this->wcStock,
            $this->moloniStock,
            $this->moloniProduct['reference']
        );
        $this->resultData = [
            'tag' => 'service:wcproduct:update:stock',
            'wc_id' => $this->wcProduct->get_id(),
            'wc_stock' => $this->wcStock,
            'ml_id' => $this->moloniProduct['product_id'],
            'ml_reference' => $this->moloniProduct['reference'],
            'ml_stock' => $this->moloniStock,
        ];
    }

    public function lockService(): void
    {
        $this->locked = true;
    }

    public function unlockService(): void
    {
        $this->locked = false;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function saveLog()
    {
        Storage::$LOGGER->info($this->resultMessage, $this->resultData);
    }

    //            Privates            //

    private function init(): void
    {
        /** Set Warehouse to use */
        if (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC > 1) {
            $this->warehouseId = (int)MOLONI_STOCK_SYNC;
        }

        $this->moloniStock = (int)MoloniProduct::parseMoloniStock($this->moloniProduct, $this->warehouseId);
        $this->wcStock = $this->wcProduct->get_stock_quantity();
    }

    //            Gets            //

    public function getWcStock(): int
    {
        return $this->wcStock;
    }

    public function getMoloniStock(): int
    {
        return $this->moloniStock;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getResultMessage(): string
    {
        return $this->resultMessage ?? '';
    }

    //            Sets            //

    public function setWcStock(?int $wcStock = 0): void
    {
        $this->wcStock = $wcStock;
    }

    public function setMoloniStock(?int $moloniStock = 0): void
    {
        $this->moloniStock = $moloniStock;
    }
}
