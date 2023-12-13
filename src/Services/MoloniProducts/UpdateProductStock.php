<?php

namespace Moloni\Services\MoloniProducts;

use WC_Product;
use Moloni\Curl;
use Moloni\Exceptions\APIExeption;
use Moloni\Exceptions\Stocks\StockException;
use Moloni\Exceptions\Stocks\StockLockedException;
use Moloni\Exceptions\Stocks\StockMatchingException;
use Moloni\Helpers\MoloniProduct;
use Moloni\Storage;

class UpdateProductStock extends ExportService
{
    private $locked = false;

    private $warehouseId = 0;

    private $wcStock = 0;
    private $moloniStock = 0;

    private $resultMessage = '';
    private $resultData = [];

    public function __construct(array $moloniProduct, WC_Product $wcProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = $wcProduct;

        $this->init();
    }

    //            Public's            //

    /**
     * Service runner
     *
     * @throws StockException
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
                __('Stock já se encontra correto no Moloni (%d|%d) (%s)'),
                $this->wcStock,
                $this->moloniStock,
                $this->moloniProduct['reference']
            );

            throw new StockMatchingException($message);
        }

        $params = [
            'product_id' => $this->moloniProduct['product_id'],
            'movement_date' => date('Y-m-d H:i:s'),
            'unit_price' => 0,
            'qty' => $this->wcStock - $this->moloniStock,
            'warehouse_id' => $this->warehouseId > 1 ? $this->warehouseId : 0,
            'notes' => 'WooCommerce',
        ];

        try {
            $request = Curl::simple('stockMovements/insert', $params);
        } catch (APIExeption $e) {
            throw new StockException($e->getMessage(), $e->getData());
        }

        if (empty($request) || empty($request['valid'])) {
            $message = sprintf(
                __('Erro a atualizar stock de produto Moloni (%s)'),
                $this->moloniProduct['reference']
            );

            throw new StockException($message, $request);
        }

        $this->resultMessage = sprintf(
            __('Stock atualizado no Moloni (antes: %s | depois: %s) (%s)'),
            $this->moloniStock,
            $this->wcStock,
            $this->moloniProduct['reference']
        );
        $this->resultData = [
            'tag' => 'service:mlproduct:update:stock',
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
