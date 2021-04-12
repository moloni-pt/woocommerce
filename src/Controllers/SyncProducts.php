<?php

namespace Moloni\Controllers;

use Exception;
use Moloni\Curl;
use Moloni\Error;
use Moloni\Log;

class SyncProducts
{

    private $since;
    private $offset = 0;
    private $limit = 5000;
    private $found = 0;
    private $updated = [];
    private $equal = [];
    private $notFound = [];

    /** @var string Switch this between outofstock or onbackorder */
    private $outOfStockStatus = 'outofstock'; //default is out of stock

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
        if (defined('MOLONI_STOCK_STATUS')) { //sets the default no stock behaviour from settings
            $this->outOfStockStatus = MOLONI_STOCK_STATUS;
        }

        $this->since = gmdate('Y-m-d H:i:s', $sinceTime);
    }

    /**
     * Run the sync operation
     * @return SyncProducts
     */
    public function run()
    {
        Log::write('A sincronizar artigos desde ' . $this->since);

        $updatedProducts = $this->getAllMoloniProducts();

        if (!empty($updatedProducts) && is_array($updatedProducts)) {
            $this->found = count($updatedProducts);
            Log::write('Encontrados ' . $this->found . ' artigos');

            foreach ($updatedProducts as $product) {
                try {
                    $wcProductId = wc_get_product_id_by_sku($product['reference']);
                    if ($product['has_stock'] && $wcProductId > 0) {
                        $currentStock = get_post_meta($wcProductId, '_stock', true);

                        /** if the product does not have the set warehouse, stock is 0 (so we do it here) */
                        $newStock = 0;


                        if ((int)MOLONI_STOCK_SYNC > 1) {
                            /** If we have a warehouse selected */
                            foreach ($product['warehouses'] as $productWarehouse) {
                                if ((int)$productWarehouse['warehouse_id'] === (int)MOLONI_STOCK_SYNC) {
                                    $newStock = $productWarehouse['stock']; // Get the stock of the particular warehouse
                                    continue;
                                }
                            }
                        } else {
                            $newStock = $product['stock'];
                        }

                        if ((float)$currentStock === (float)$newStock) {
                            Log::write('Artigo com a referência ' . $product['reference'] . ' já tem o stock correcto ' . $currentStock . '|' . $newStock);
                            $this->equal[$product['reference']] = 'Artigo com a referência ' . $product['reference'] . ' já tem o stock correcto';
                        } else {
                            Log::write('Artigo com a referência ' . $product['reference'] . ' foi actualizado de ' . $currentStock . ' para ' . $newStock);
                            $this->updated[$product['reference']] = 'Artigo com a referência ' . $product['reference'] . ' foi actualizado de ' . $currentStock . ' para ' . $newStock;
                            wc_update_product_stock($wcProductId, $newStock);
                        }
                    } else {
                        Log::write('Artigo não encontrado ou sem stock ativo: ' . $product['reference']);
                        $this->notFound[$product['reference']] = 'Artigo não encontrado no WooCommerce ou sem stock activo';
                    }
                } catch (Exception $error) {
                    Log::write('Erro: ' . $error->getMessage());
                }
            }
        } else {
            Log::write(__('Sem artigos para atualizar desde ') . $this->since);
        }

        return $this;
    }

    /**
     * Get the amount of records found
     * @return int
     */
    public function countFoundRecord()
    {
        return $this->found;
    }

    /**
     * Get the amount of records update
     * @return int
     */
    public function countUpdated()
    {
        return count($this->updated);
    }

    /**
     * Get the amount of records that had the same stock count
     * @return int
     */
    public function countEqual()
    {
        return count($this->equal);
    }

    /**
     * Get the amount of products not found in WooCommerce
     * @return int
     */
    public function countNotFound()
    {
        return count($this->notFound);
    }

    /**
     * Return the updated products
     * @return array
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Return the list of products that had the same stock as in WooCommerce
     * @return array
     */
    public function getEqual()
    {
        return $this->equal;
    }

    /**
     * Return the list of products update in Moloni but not found in WooCommerce
     * @return array
     */
    public function getNotFound()
    {
        return $this->notFound;
    }

    /**
     * Each request brings a maximum of 50 products
     * While there are more products we keep asking for more
     * @return array
     */
    private function getAllMoloniProducts()
    {
        $productsList = [];

        while (true) {
            $values = [
                'company_id' => MOLONI_COMPANY_ID,
                'lastmodified' => $this->since,
                'offset' => $this->offset
            ];

            Log::write(json_encode($values));

            try {
                $fetched = Curl::simple('products/getModifiedSince', $values);
            } catch (Error $e) {
                $fetched = [];
                Log::write('Atenção, erro ao obter todos os artigos via API');
            }

            /** Fail safe - When a request brings no product at all */
            if (isset($fetched[0]['product_id'])) {

                foreach ($fetched as $item) {
                    $productsList[] = $item;
                }

                $this->offset += count($fetched);
            } else {
                break;
            }

            /** If the requests does not bring the maximum of 50 products */
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
