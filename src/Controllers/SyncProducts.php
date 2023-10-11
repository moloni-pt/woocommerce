<?php

namespace Moloni\Controllers;

use Exception;
use Moloni\Exceptions\APIExeption;
use Moloni\Curl;
use Moloni\Storage;

class SyncProducts
{
    private $since;
    private $offset = 0;
    private $limit = 5000;
    private $found = 0;
    private $updated = [];
    private $equal = [];
    private $notFound = [];

    public function __construct($since)
    {
        if (is_numeric($since)) {
            $sinceTime = $since;
        } else {
            $sinceTime = strtotime($since);
            if (!$sinceTime) {
                $sinceTime = strtotime('-1 week');
            }
        }

        $this->since = gmdate('Y-m-d H:i:s', $sinceTime);
    }

    /**
     * Run the sync operation
     */
    public function run(): SyncProducts
    {
        $updatedProducts = $this->getAllMoloniProducts();

        if (!empty($updatedProducts) && is_array($updatedProducts)) {
            $this->found = count($updatedProducts);

            foreach ($updatedProducts as $product) {
                try {
                    $wcProductId = wc_get_product_id_by_sku($product['reference']);

                    if ($product['has_stock'] && $wcProductId > 0) {
                        $wcProduct = wc_get_product($wcProductId);
                        $currentStock = $wcProduct->get_stock_quantity();

                        /** if the product does not have the set warehouse, stock is 0 (so we do it here) */
                        $newStock = 0;

                        if ((int)MOLONI_STOCK_SYNC > 1) {
                            /** If we have a warehouse selected */
                            foreach ($product['warehouses'] as $productWarehouse) {
                                if ((int)$productWarehouse['warehouse_id'] === (int)MOLONI_STOCK_SYNC) {
                                    $newStock = $productWarehouse['stock']; // Get the stock of the particular warehouse
                                    break;
                                }
                            }
                        } else {
                            $newStock = $product['stock'];
                        }

                        if ((float)$currentStock === (float)$newStock) {
                            $msg = 'Artigo com a referência ' . $product['reference'] . ' já tem o stock correcto ' . $currentStock . '|' . $newStock;

                            $this->equal[$product['reference']] = $msg;
                        } else {
                            $msg = 'Artigo com a referência ' . $product['reference'] . ' foi actualizado de ' . $currentStock . ' para ' . $newStock;

                            $this->updated[$product['reference']] = $msg;
                            wc_update_product_stock($wcProduct, $newStock);
                        }
                    } else {
                        $msg = 'Artigo com a referência ' . $product['reference'] . ' não encontrado ou sem stock ativo';

                        $this->notFound[$product['reference']] = $msg;
                    }
                } catch (Exception $error) {
                    Storage::$LOGGER->critical(__('Erro fatal'), [
                        'action' => 'stock:sync:service',
                        'exception' => $error->getMessage()
                    ]);
                }
            }
        }

        return $this;
    }

    /**
     * Get the amount of records found
     *
     * @return int
     */
    public function countFoundRecord(): int
    {
        return $this->found;
    }

    /**
     * Get the amount of records updates
     *
     * @return int
     */
    public function countUpdated(): int
    {
        return count($this->updated);
    }

    /**
     * Get the amount of records that had the same stock count
     *
     * @return int
     */
    public function countEqual(): int
    {
        return count($this->equal);
    }

    /**
     * Get the amount of products not found in WooCommerce
     *
     * @return int
     */
    public function countNotFound(): int
    {
        return count($this->notFound);
    }

    /**
     * Get date used to fetch
     *
     * @return false|string
     */
    public function getSince()
    {
        return $this->since ?? '';
    }

    /**
     * Return the updated products
     *
     * @return array
     */
    public function getUpdated(): array
    {
        return $this->updated;
    }

    /**
     * Return the list of products that had the same stock as in WooCommerce
     *
     * @return array
     */
    public function getEqual(): array
    {
        return $this->equal;
    }

    /**
     * Return the list of products update in Moloni but not found in WooCommerce
     *
     * @return array
     */
    public function getNotFound(): array
    {
        return $this->notFound;
    }

    /**
     * Each request brings a maximum of 50 products
     * While there are more products we keep asking for more
     *
     * @return array
     */
    private function getAllMoloniProducts(): array
    {
        $productsList = [];

        while (true) {
            $values = [
                'company_id' => Storage::$MOLONI_COMPANY_ID,
                'lastmodified' => $this->since,
                'offset' => $this->offset
            ];

            try {
                $fetched = Curl::simple('products/getModifiedSince', $values);
            } catch (APIExeption $e) {
                $fetched = [];

                Storage::$LOGGER->error(__('Atenção, erro ao obter todos os artigos via API'), [
                    'action' => 'stock:sync:service',
                    'message' => $e->getMessage(),
                    'exception' => $e->getData(),
                ]);
            }

            /** Fail-safe - When a request brings no product at all */
            if (isset($fetched[0]['product_id'])) {
                foreach ($fetched as $item) {
                    $productsList[] = $item;
                }

                $this->offset += count($fetched);
            } else {
                break;
            }

            /** If the requests do not bring the maximum of 50 products */
            if (count($fetched) < 50) {
                break;
            }

            /** If the products list is bigger than the limit defined */
            if (count($productsList) > $this->limit) {
                break;
            }
        }

        return $productsList;
    }

}
