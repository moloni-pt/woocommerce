<?php
/**
 *
 *   Plugin Name:  Moloni
 *   Plugin URI:   https://plugins.moloni.com/woocommerce
 *   Description:  Send your orders automatically to your Moloni invoice software
 *   Version:      0.0.1
 *   Author:       Moloni.com
 *   Author URI:   https://moloni.com
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace Moloni\Controllers;

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

    public function __construct($since)
    {
        $sinceTime = strtotime($since);
        if (!$sinceTime) {
            $sinceTime = strtotime("-1 week");
        }

        $this->since = gmdate("Y-m-d H:i:s", $sinceTime);
    }

    /**
     * Run the sync operation
     * @return SyncProducts
     */
    public function run()
    {
        Log::write("A sincronizar artigos desde " . $this->since);
        $updatedProducts = $this->getAllMoloniProducts();
        if (!empty($updatedProducts) && is_array($updatedProducts)) {
            $this->found = count($updatedProducts);
            foreach ($updatedProducts as $product) {
                try {
                    $wcProductId = wc_get_product_id_by_sku($product['reference']);
                    if ($product['has_stock'] && $wcProductId > 0) {
                        Log::write("Sincronizar artigo: " . $product['reference']);
                        $currentStock = get_post_meta($wcProductId, '_stock', true);
                        $newStock = $product['stock'];

                        if ($currentStock == $newStock) {
                            Log::write("Artigo com a referência " . $product['reference'] . " já tem o stock correcto");
                            $this->equal[$product['reference']] = "Artigo com a referência " . $product['reference'] . " já tem o stock correcto";
                        } else {
                            Log::write("Artigo com a referência " . $product['reference'] . " foi actualizado de " . $currentStock . " para " . $newStock);
                            $this->updated[$product['reference']] = "Artigo com a referência " . $product['reference'] . " foi actualizado de " . $currentStock . " para " . $newStock;
                            update_post_meta($wcProductId, '_stock', $product['stock']);
                            update_post_meta($wcProductId, '_stock_status', ($product['stock'] > 0 ? 'instock' : 'outofstock'));
                            update_post_meta($wcProductId, 'outofstock', ($product['stock'] > 0 ? false : true));
                        }
                    } else {
                        Log::write("Artigo não encontrado ou sem stock ativo: " . $product['reference']);
                        $this->notFound[$product['reference']] = "Artigo não encontrado no WooCommerce ou sem stock activo";
                    }
                } catch (\Exception $error) {
                    echo "<br>" . $error->getMessage();
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
                "company_id" => COMPANY_ID,
                "lastmodified" => $this->since,
                "offset" => $this->offset
            ];

            try {
                $fetched = Curl::simple("products/getModifiedSince", $values);
            } catch (Error $e) {
                $fetched = [];
                Log::write("Atenção, erro ao obter todos os artigos via API");
            }

            /** Fail safe - When a request brings no product at all */
            if (isset($fetched[0]['product_id'])) {
                $productsList = array_merge($productsList, $fetched);
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
