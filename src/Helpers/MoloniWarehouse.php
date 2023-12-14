<?php

namespace Moloni\Helpers;

use Moloni\Curl;
use Moloni\Exceptions\APIExeption;

class MoloniWarehouse
{
    /**
     * Get Moloni warehouse by ID
     *
     * @throws APIExeption
     */
    public static function getWarehouseById(int $targetId): array
    {
        $warehouses = Curl::simple('warehouses/getAll', []);

        if (is_array($warehouses) && !empty($warehouses)) {
            foreach ($warehouses as $warehouse) {
                if ((int)$warehouse['warehouse_id'] === $targetId) {
                    return $warehouse;
                }
            }
        }

        return [];
    }
}
