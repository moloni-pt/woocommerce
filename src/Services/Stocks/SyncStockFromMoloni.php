<?php

namespace Moloni\Services\Stocks;

use Moloni\Curl;
use Moloni\Storage;
use Moloni\Exceptions\APIExeption;
use Moloni\Exceptions\Stocks\StockLockedException;
use Moloni\Exceptions\Stocks\StockMatchingException;
use Moloni\Services\WcProducts\UpdateProductStock;

class SyncStockFromMoloni
{
    private $since;

    private $offset = 0;
    private $limit = 5000;
    private $found = 0;

    private $updated = [];
    private $equal = [];
    private $notFound = [];
    private $locked = [];

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

    //            Publics            //

    /**
     * Run the sync operation
     */
    public function run(): SyncStockFromMoloni
    {
        $updatedProducts = $this->getAllMoloniProducts();

        if (empty($updatedProducts)) {
            return $this;
        }

        $this->found = count($updatedProducts);

        foreach ($updatedProducts as $product) {
            if (empty($product['has_stock'])) {
                $this->notFound[$product['reference']] = __('Artigo sem stock ativo');

                continue;
            }

            $wcProductId = wc_get_product_id_by_sku($product['reference']);

            if ($wcProductId <= 0) {
                $this->notFound[$product['reference']] = __('Artigo não encontrado');

                continue;
            }

            $wcProduct = wc_get_product($wcProductId);

            try {
                $service = new UpdateProductStock($wcProduct, $product);

                do_action('moloni_before_product_stock_sync', $service);

                $service->run();

                $this->updated[$product['reference']] = $service->getResultMessage();
            } catch (StockMatchingException $error) {
                $this->equal[$product['reference']] = $error->getMessage();
            } catch (StockLockedException $error) {
                $this->locked[$product['reference']] = $error->getMessage();
            }
        }

        return $this;
    }

    //            Counts            //

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
     * Get the amount of products locked
     *
     * @return int
     */
    public function countLocked(): int
    {
        return count($this->locked);
    }

    //            Gets            //

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
     * Return the list of products locked
     *
     * @return array
     */
    public function getLocked(): array
    {
        return $this->locked;
    }

    //            Auxiliary            //

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
