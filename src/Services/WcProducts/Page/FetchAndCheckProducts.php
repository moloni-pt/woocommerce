<?php

namespace Moloni\Services\WcProducts\Page;

use WC_Product;
use Moloni\Curl;
use Moloni\Helpers\MoloniProduct;
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

        /** @var WC_Product $product */
        foreach ($this->products as $product) {
            $this->rows[] = [
                'tool_show_create_button' => false,
                'tool_show_update_stock_button' => false,
                'tool_alert_message' => '',
                'wc_product_id' => $product->get_id(),
                'wc_product_link' => '',
                'wc_product_object' => $product,
                'moloni_product_id' => 0,
                'moloni_product_array' => [],
                'moloni_product_link' => ''
            ];

            end($this->rows);
            $row = &$this->rows[key($this->rows)];

            $this->createWcLink($row);

            if (empty($product->get_sku())) {
                $row['tool_alert_message'] = __('Produto WooCommerce sem referência');

                continue;
            }

            $mlProduct = Curl::simple('products/getByReference', ['reference' => $product->get_sku(), 'with_invisible' => true, 'exact' => 1]);

            if (empty($mlProduct)) {
                $row['tool_show_create_button'] = true;
                $row['tool_alert_message'] = __('Produto não encontrado na conta Moloni');

                continue;
            }

            $mlProduct = $mlProduct[0];

            $row['moloni_product_id'] = $mlProduct['product_id'];
            $row['moloni_product_array'] = $mlProduct;

            $this->createMoloniLink($row);

            if (!defined('MOLONI_STOCK_SYNC') || empty(MOLONI_STOCK_SYNC)) {
                continue;
            }

            if (!empty($mlProduct['has_stock']) !== $product->managing_stock()) {
                $row['tool_alert_message'] = __('Estado do controlo de stock diferente');

                continue;
            }

            if (!empty($mlProduct['has_stock'])) {
                $wcStock = (int)$product->get_stock_quantity();
                $moloniStock = (int)MoloniProduct::parseMoloniStock(
                    $mlProduct,
                    defined('MOLONI_STOCK_SYNC') ? (int)MOLONI_STOCK_SYNC : 1
                );

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
            'total' => ceil($this->totalProducts / self::$perPage),
        ];

        return paginate_links($args);
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
        $wcProductId = $row['wc_product_object']->get_id();

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
        /**
         * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
         */
        $filters = [
            'status' => ['publish'],
            'limit' => self::$perPage,
            'page' => $this->page,
            'paginate' => true,
            'orderby' => [
                'ID' => 'DESC',
            ],
        ];

        if (!empty($this->filters['filter_reference'])) {
            $filters['sku'] = $this->filters['filter_reference'];
        }

        if (!empty($this->filters['filter_name'])) {
            $filters['name'] = $this->filters['filter_name'];
        }

        $query = wc_get_products($filters);

        $this->products = $query->products ?? [];
        $this->totalProducts = (int)($query->total ?? 0);
    }
}
