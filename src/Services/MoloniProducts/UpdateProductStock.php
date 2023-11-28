<?php

namespace Moloni\Services\MoloniProducts;

use WC_Product;
use Moloni\Exceptions\Stocks\StockLockedException;
use Moloni\Exceptions\Stocks\StockMatchingException;

class UpdateProductStock
{
    private $locked = false;

    private $wcProduct;
    private $moloniProduct;

    private $warehouseId = 0;

    private $wcStock = 0;
    private $moloniStock = 0;

    private $resultMessage = '';

    public function __construct(WC_Product $wcProduct, array $moloniProduct)
    {
        $this->wcProduct = $wcProduct;
        $this->moloniProduct = $moloniProduct;

        $this->init();
    }

    //            Publics            //

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
            $message = sprintf(__('Artigo já tem o stock correto %d|%d'), $this->wcStock, $this->moloniStock);

            throw new StockMatchingException($message);
        }

        wc_update_product_stock($this->wcProduct, $this->moloniStock);

        $this->resultMessage = sprintf(__('Artigo foi actualizado de %d para %d'), $this->wcStock, $this->moloniStock);
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

    //            Privates            //

    private function init(): void
    {
        /** Set Warehouse to use */
        if (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC > 1) {
            $this->warehouseId = (int)MOLONI_STOCK_SYNC;
        }

        $moloniStock = 0;

        if ($this->warehouseId > 1) {
            foreach ($this->moloniProduct['warehouses'] as $productWarehouse) {
                if ((int)$productWarehouse['warehouse_id'] === $this->warehouseId) {
                    $moloniStock = $productWarehouse['stock']; // Get the stock of the particular warehouse

                    break;
                }
            }
        } else {
            $moloniStock = $this->moloniProduct['stock'];
        }

        $this->moloniStock = (int)$moloniStock;
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

    public function getMoloniProduct(): array
    {
        return $this->moloniProduct;
    }

    public function getWcProduct(): WC_Product
    {
        return $this->wcProduct;
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
