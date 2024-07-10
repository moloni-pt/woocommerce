<?php

use Moloni\Exceptions\APIExeption;
use Moloni\Helpers\MoloniWarehouse;
use Moloni\Services\WcProducts\Page\FetchAndCheckProducts;

if (!defined('ABSPATH')) {
    exit;
}

$page = (int)($_REQUEST['paged'] ?? 1);
$filters = [
    'filter_name' => sanitize_text_field($_REQUEST['filter_name'] ?? ''),
    'filter_reference' => sanitize_text_field($_REQUEST['filter_reference'] ?? ''),
];

$service = new FetchAndCheckProducts();
$service->setPage($page);
$service->setFilters($filters);

try {
    $service->run();
} catch (APIExeption $e) {
    $e->showError();
    return;
}

$rows = $service->getRows();
$paginator = $service->getPaginator();

$currentAction = admin_url('admin.php?page=moloni&tab=wcProductsList');
$backAction = admin_url('admin.php?page=moloni&tab=tools');
?>

<h3>
    <?php esc_html_e('Listagem de produtos WooCommerce') ?>
</h3>

<h4>
    <?php esc_html_e('Nesta listagem serão apresentados todos os produtos WooCommerce da loja atual e indicados erros/alertas que possam existir.') ?>
    <?php esc_html_e('Todas as ações nesta página serão na direção WooCommerce -> Moloni.') ?>
</h4>

<div class="notice notice-warning m-0">
    <p>
        <?php esc_html_e('Valores do stock Moloni baseados em:') ?>
    </p>
    <p>
        <?php
        $warehouseId = $service->getWarehouseId();

        if ($warehouseId === 0) {
            echo '- ' . esc_html__('Stock acumulado de todos os armazéns.');
        } else {
            try {
                $warehouse = MoloniWarehouse::getWarehouseById($warehouseId);
            } catch (APIExeption $e) {
                $e->showError();
                return;
            }

            echo '- ' . esc_html__('Armazém');
            echo ': ' . esc_html($warehouse['title'] . ' (' . $warehouse['code'] . ')');
        }
        ?>
    </p>
</div>

<form method="get" action='<?= esc_url($currentAction) ?>' class="list_form">
    <input type="hidden" name="page" value="moloni">
    <input type="hidden" name="paged" value="<?= esc_html($page) ?>">
    <input type="hidden" name="tab" value="wcProductsList">

    <div class="tablenav top">
        <a href='<?= esc_url($backAction) ?>' class="button button-large">
            <?php esc_html_e('Voltar') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?php esc_html_e('Correr exportações') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>

    <table class="wp-list-table widefat striped posts">
        <thead>
        <tr>
            <th>
                <a><?php esc_html_e('Nome') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Referência') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Tipo') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Alertas') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Exportar produto') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Exportar Stock') ?></a>
            </th>
        </tr>
        <tr>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_name"
                        value="<?= esc_html($filters['filter_name']) ?>"
                >
            </th>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_reference"
                        value="<?= esc_html($filters['filter_reference']) ?>"
            </th>
            <th></th>
            <th></th>
            <th class="flex flex-row gap-2">
                <button type="button" class="search_button button button-primary">
                    <?php esc_html_e('Pesquisar') ?>
                </button>

                <a href='<?= esc_url($currentAction) ?>' class="button">
                    <?php esc_html_e('Limpar') ?>
                </a>
            </th>
            <th>
                <div class="text-center">
                    <input type="checkbox" class="checkbox_create_product_master m-0-important">
                </div>
            </th>
            <th>
                <div class="text-center">
                    <input type="checkbox" class="checkbox_update_stock_product_master m-0-important">
                </div>
            </th>
        </tr>
        </thead>

        <tbody>
        <?php if (!empty($rows) && is_array($rows)) : ?>
            <?php foreach ($rows as $row) : ?>
                <?= wp_kses_post($row) ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr class="text-center">
                <td colspan="100%">
                    <?php esc_html_e('Não foram encontados produtos WooCommerce!') ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>

        <tfoot>
        <tr>
            <th>
                <a><?php esc_html_e('Nome') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Referência') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Tipo') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Alertas') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Exportar produto') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Exportar Stock') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?= esc_url($backAction) ?>' class="button button-large">
            <?php esc_html_e('Voltar') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?php esc_html_e('Correr exportações') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>
</form>

<?php include MOLONI_TEMPLATE_DIR . 'Modals/Products/ActionModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.WcProducts.init();
    });
</script>

