<?php

namespace Moloni\Helpers;

use Moloni\Curl;
use Moloni\Exceptions\APIExeption;

class MoloniProduct
{
    public static function parseMoloniStock(array $moloniProduct, int $warehouseId): float
    {
        $stock = 0.0;

        if ($warehouseId > 1) {
            foreach ($moloniProduct['warehouses'] as $productWarehouse) {
                if ((int)$productWarehouse['warehouse_id'] === $warehouseId) {
                    $stock = (float)$productWarehouse['stock']; // Get the stock of the particular warehouse

                    break;
                }
            }
        } else {
            $stock = (float)$moloniProduct['stock'];
        }

        return $stock;
    }

    public static function getWarehouseIdForManualDataSyncTools(): int
    {
        if (defined('MOLONI_STOCK_SYNC') && !empty(MOLONI_STOCK_SYNC)) {
            if ((int)MOLONI_STOCK_SYNC === 1) {
                return 0;
            }

            return (int)MOLONI_STOCK_SYNC;
        }

        if (defined('MOLONI_PRODUCT_WAREHOUSE')) {
            if ((int)MOLONI_PRODUCT_WAREHOUSE === 0) {
                try {
                    $defaultWarehouse = Curl::simple('warehouses/getDefaultWarehouse', []);
                } catch (APIException $e) {
                    $defaultWarehouse = [];
                }

                if (!empty($defaultWarehouse) && !empty($defaultWarehouse['warehouse_id'])) {
                    return (int)$defaultWarehouse['warehouse_id'];
                }
            }

            return (int)MOLONI_PRODUCT_WAREHOUSE;
        }

        return 0;
    }
}
