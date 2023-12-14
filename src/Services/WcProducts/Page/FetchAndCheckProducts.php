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

        foreach ($this->products as $product) {
            $this->checkProduct($product);
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

    //            Private's            //

    private function checkProduct(WC_Product $product)
    {
        $this->rows[] = [
            'tool_show_create_button' => false,
            'tool_show_update_stock_button' => false,
            'tool_alert_message' => '',
            'wc_product_id' => $product->get_id(),
            'wc_product_parent_id' => $product->get_parent_id(),
            'wc_product_link' => '',
            'wc_product_object' => $product,
            'moloni_product_id' => 0,
            'moloni_product_array' => [],
            'moloni_product_link' => ''
        ];

        end($this->rows);
        $row = &$this->rows[key($this->rows)];

        if ($product->is_type('variable') && $product->has_child()) {
            $this->checkParentProduct($row, $product);

            $children = $product->get_children();

            foreach ($children as $child) {
                $childObject = wc_get_product($child);

                $this->checkProduct($childObject);
            }
        } else {
            $this->checkNormalProduct($row, $product);
        }
    }

    private function checkParentProduct(array &$row, WC_Product $product)
    {
        $this->createWcLink($row);

        if ($product->managing_stock()) {
            $row['tool_alert_message'] = __('Gestão de stock deve ser efetuada ao nível das variações');

            return;
        }
    }

    private function checkNormalProduct(array &$row, WC_Product $product)
    {
        /** Child products do not have their own page */
        if (empty($product->get_parent_id())) {
            $this->createWcLink($row);
        }

        if (empty($product->get_sku())) {
            $row['tool_alert_message'] = __('Produto WooCommerce sem referência');

            return;
        }

        $mlProduct = Curl::simple('products/getByReference', ['reference' => $product->get_sku(), 'with_invisible' => true, 'exact' => 1]);

        if (empty($mlProduct)) {
            $row['tool_show_create_button'] = true;
            $row['tool_alert_message'] = __('Produto não encontrado na conta Moloni');

            return;
        }

        $mlProduct = $mlProduct[0];

        $row['moloni_product_id'] = $mlProduct['product_id'];
        $row['moloni_product_array'] = $mlProduct;

        $this->createMoloniLink($row);

        if (!empty($mlProduct['has_stock']) !== $product->managing_stock()) {
            $row['tool_alert_message'] = __('Estado do controlo de stock diferente');

            return;
        }

        if (!empty($mlProduct['has_stock'])) {
            $wcStock = (int)$product->get_stock_quantity();
            $moloniStock = (int)MoloniProduct::parseMoloniStock($mlProduct, $this->warehouseId);

            if ($wcStock !== $moloniStock) {
                $row['tool_show_update_stock_button'] = true;
                $row['tool_alert_message'] = __('Stock não coincide no WooCommerce e Moloni');
                $row['tool_alert_message'] .= " (Moloni:$moloniStock | WooCommerce: $wcStock)";

                return;
            }
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

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    //            Sets            //

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    //            Requests            //

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
