<?php

namespace Moloni\Services\MoloniProducts\Page;

use WC_Product;
use Moloni\Curl;
use Moloni\Enums\Domains;
use Moloni\Exceptions\APIExeption;

class FetchAndCheckProducts
{
    private static $perPage = 20;

    private $page = 1;
    private $filters = [];

    private $rows = [];

    private $products = [];
    private $totalProducts = 0;

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
        $this->fetchProducts();

        if (!array($this->products)) {
            $this->products = [];
        }

        $this->countProducts();

        foreach ($this->products as $product) {
            if (!isset($product['product_id']) || !isset($product['reference'])) {
                continue;
            }

            $this->rows[] = [
                'tool_show_create_button' => false,
                'tool_show_update_stock_button' => false,
                'tool_alert_message' => '',
                'wc_product_id' => 0,
                'wc_product_link' => '',
                'wc_product_object' => null,
                'moloni_product_id' => $product['product_id'],
                'moloni_product_array' => $product,
                'moloni_product_link' => ''
            ];

            end($this->rows);
            $row = &$this->rows[key($this->rows)];

            $this->createMoloniLink($row);

            if (in_array(strtolower($product['reference']), ['portes', 'envio', 'shipping'])) {
                $row['tool_alert_message'] = __('Produto bloqueado');
                continue;
            }

            $wcProductId = wc_get_product_id_by_sku($product['reference']);

            if (empty($wcProductId)) {
                $row['tool_show_create_button'] = true;
                $row['tool_alert_message'] = __('Produto não encontrado na loja WooCommerce');

                continue;
            }

            $wcProduct = wc_get_product($wcProductId);

            $row['wc_product_id'] = $wcProductId;
            $row['wc_product_object'] = $wcProduct;

            $this->createWcLink($row);

            if ($wcProduct->is_type('variable')) {
                $row['tool_alert_message'] = __('Produto WooCommerce tem variantes');

                continue;
            }

            if (!empty($product['has_stock']) !== $wcProduct->managing_stock()) {
                $row['tool_alert_message'] = __('Estado do controlo de stock diferente');

                continue;
            }

            if (!empty($product['has_stock'])) {
                $wcStock = (int)$wcProduct->get_stock_quantity();
                $moloniStock = (int)$product['stock'];

                if ($wcStock !== $moloniStock) {
                    $row['tool_show_update_stock_button'] = true;
                    $row['tool_alert_message'] = __('Stock não coincide no WooCommerce e Moloni');

                    continue;
                }
            }
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

    //            Auxiliary            //

    private function createMoloniLink(array &$row)
    {
        $row['moloni_product_link'] = Domains::HOMEPAGE . '/';

        if (defined('COMPANY_SLUG')) {
            $row['moloni_product_link'] .= COMPANY_SLUG;
        } else {
            $row['moloni_product_link'] .= 'ac';
        }

        $row['moloni_product_link'] .= '/Artigos/showUpdate/';
        $row['moloni_product_link'] .= $row['moloni_product_array']['product_id'];
        $row['moloni_product_link'] .= '/';
        $row['moloni_product_link'] .= $row['moloni_product_array']['category_id'];
    }

    private function createWcLink(array &$row)
    {
        $wcProductId = $row['wc_product_id'];

        $row['wc_product_link'] = admin_url("post.php?post=$wcProductId&action=edit");
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
