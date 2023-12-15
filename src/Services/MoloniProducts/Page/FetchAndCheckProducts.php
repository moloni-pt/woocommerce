<?php

namespace Moloni\Services\MoloniProducts\Page;

use Moloni\Curl;
use Moloni\Enums\Domains;
use Moloni\Helpers\MoloniProduct;
use Moloni\Exceptions\APIExeption;

class FetchAndCheckProducts
{
    private static $perPage = 20;

    private $page = 1;
    private $filters = [];

    private $rows = [];

    private $products = [];
    private $totalProducts = 0;

    private $warehouseId = 0;

    //            Public's            //

    /**
     * Service runner
     *
     * @return void
     *
     * @throws APIExeption
     */
    public function run()
    {
        $this->warehouseId = MoloniProduct::getWarehouseIdForManualDataSyncTools();

        $this->fetchProducts();

        if (!array($this->products)) {
            $this->products = [];
        }

        $this->countProducts();

        foreach ($this->products as $product) {
            if (!isset($product['product_id']) || !isset($product['reference'])) {
                continue;
            }

            $service = new CheckProduct($product, $this->warehouseId);
            $service->run();

            $this->rows[] = $service->getRow();
        }
    }

    public function getPaginator()
    {
        $baseArguments = add_query_arg([
            'paged' => '%#%',
            'filter_name' => $this->filters['filter_name'],
            'filter_reference' => $this->filters['filter_reference'],
        ]);

        $args = [
            'base' => $baseArguments,
            'format' => '',
            'current' => $this->page,
            'total' => ceil(($this->totalProducts + (($this->page - 1) * self::$perPage)) / self::$perPage),
        ];

        return paginate_links($args);
    }

    //            Privates            //

    private function countProducts()
    {
        $this->totalProducts = count($this->products);

        if ($this->totalProducts === self::$perPage + 1) {
            array_pop($this->products);
        }
    }

    //            Gets            //

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getTotalProducts(): int
    {
        return $this->totalProducts;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    //            Requests            //

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    //            Sets            //

    /**
     * Fetch products from Moloni
     *
     * @throws APIExeption
     */
    private function fetchProducts()
    {
        $filtersActive = false;

        $fetchParams = [
            'qty' => self::$perPage + 1,
            'offset' => (($this->page - 1) * self::$perPage),
            'exact' => 0,
            'order_by_field' => 'reference',
            'order_by_ordering' => 'asc'
        ];

        if (!empty($this->filters['filter_reference'])) {
            $fetchParams['search'] = $this->filters['filter_reference'];

            $filtersActive = true;
        } elseif (!empty($this->filters['filter_name'])) {
            $fetchParams['search'] = $this->filters['filter_name'];

            $filtersActive = true;
        }

        if ($filtersActive) {
            $this->products = Curl::simple('products/getBySearch', $fetchParams);

            return;
        }

        $this->products = Curl::simple('products/getAll', $fetchParams);
    }
}
