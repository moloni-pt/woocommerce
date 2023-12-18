<?php

namespace Moloni\Services\MoloniProducts\Page;

use Moloni\Enums\Domains;
use Moloni\Helpers\MoloniProduct;

class CheckProduct
{
    private $product;
    private $warehouseId;
    private $row;

    public function __construct(array $product, int $warehouseId)
    {
        $this->product = $product;
        $this->warehouseId = $warehouseId;

        $this->row = [
            'tool_show_create_button' => false,
            'tool_show_update_stock_button' => false,
            'tool_alert_message' => '',
            'wc_product_id' => 0,
            'wc_product_parent_id' => 0,
            'wc_product_link' => '',
            'wc_product_object' => null,
            'moloni_product_id' => $this->product['product_id'],
            'moloni_product_array' => $this->product,
            'moloni_product_link' => ''
        ];
    }

    public function run()
    {
        $this->row = [
            'tool_show_create_button' => false,
            'tool_show_update_stock_button' => false,
            'tool_alert_message' => '',
            'wc_product_id' => 0,
            'wc_product_parent_id' => 0,
            'wc_product_link' => '',
            'wc_product_object' => null,
            'moloni_product_id' => $this->product['product_id'],
            'moloni_product_array' => $this->product,
            'moloni_product_link' => ''
        ];

        $this->createMoloniLink();

        if (in_array(strtolower($this->product['reference']), ['portes', 'envio', 'shipping'])) {
            $this->row['tool_alert_message'] = __('Produto bloqueado');
            return;
        }

        $wcProductId = wc_get_product_id_by_sku($this->product['reference']);

        if (empty($wcProductId)) {
            $this->row['tool_show_create_button'] = true;
            $this->row['tool_alert_message'] = __('Produto não encontrado na loja WooCommerce');

            return;
        }

        $wcProduct = wc_get_product($wcProductId);

        $this->row['wc_product_id'] = $wcProduct->get_id();
        $this->row['wc_product_parent_id'] = $wcProduct->get_parent_id();
        $this->row['wc_product_object'] = $wcProduct;

        $this->createWcLink($this->row);

        if ($wcProduct->is_type('variable')) {
            $this->row['tool_alert_message'] = __('Produto WooCommerce tem variantes');

            return;
        }

        if (!empty($this->product['has_stock']) !== $wcProduct->managing_stock()) {
            $this->row['tool_alert_message'] = __('Estado do controlo de stock diferente');

            return;
        }

        if (!empty($this->product['has_stock'])) {
            $wcStock = (int)$wcProduct->get_stock_quantity();
            $moloniStock = (int)MoloniProduct::parseMoloniStock($this->product, $this->warehouseId);

            if ($wcStock !== $moloniStock) {
                $this->row['tool_show_update_stock_button'] = true;
                $this->row['tool_alert_message'] = __('Stock não coincide no WooCommerce e Moloni');
                $this->row['tool_alert_message'] .= " (Moloni: $moloniStock | WooCommerce: $wcStock)";

                return;
            }
        }
    }

    //            Gets            //

    public function getRow(): array
    {
        return $this->row;
    }

    public function getRowsHtml(): string
    {
        $row = $this->row;

        ob_start();

        include MOLONI_TEMPLATE_DIR . 'Blocks/MoloniProduct/ProductRow.php';

        return ob_get_clean() ?: '';
    }
    //            Auxiliary            //

    private function createMoloniLink()
    {
        $this->row['moloni_product_link'] = Domains::HOMEPAGE . '/';

        if (defined('COMPANY_SLUG')) {
            $this->row['moloni_product_link'] .= COMPANY_SLUG;
        } else {
            $this->row['moloni_product_link'] .= 'ac';
        }

        $this->row['moloni_product_link'] .= '/Artigos/showUpdate/';
        $this->row['moloni_product_link'] .= $this->row['moloni_product_array']['product_id'];
        $this->row['moloni_product_link'] .= '/';
        $this->row['moloni_product_link'] .= $this->row['moloni_product_array']['category_id'];
    }

    private function createWcLink()
    {
        $wcProductId = $this->row['wc_product_id'];

        $this->row['wc_product_link'] = admin_url("post.php?post=$wcProductId&action=edit");
    }
}
