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
    <?= __('Listagem de produtos WooCommerce') ?>
</h3>

<h4>
    <?= __('Nesta listagem serão apresentados todos os produtos WooCommerce da loja atual e indicados erros/alertas que possam existir.') ?>
    <?= __('Todas as ações nesta página serão na direção WooCommerce -> Moloni.') ?>
</h4>

<div class="notice notice-warning m-0">
    <p>
        <?= __('Valores do stock Moloni baseados em:') ?>
    </p>
    <p>
        <?php
        $warehouseId = $service->getWarehouseId();

        if ($warehouseId === 0) {
            echo '- ' . __('Stock acumulado de todos os armazéns.');
        } else {
            try {
                $warehouse = MoloniWarehouse::getWarehouseById($warehouseId);
            } catch (APIExeption $e) {
                $e->showError();
                return;
            }

            echo '- ' . __('Armazém');
            echo ': ' . $warehouse['title'] . ' (' . $warehouse['code'] . ')';
        }
        ?>
    </p>
</div>

<form method="get" action='<?= $currentAction ?>'>
    <input type="hidden" name="page" value="moloni">
    <input type="hidden" name="paged" value="<?= $page ?>">
    <input type="hidden" name="tab" value="wcProductsList">

    <div class="tablenav top">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Voltar') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?= __('Correr exportações') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>

    <table class="wp-list-table widefat striped posts">
        <thead>
        <tr>
            <th>
                <a><?= __('Nome') ?></a>
            </th>
            <th>
                <a><?= __('Referência') ?></a>
            </th>
            <th>
                <a><?= __('Tipo') ?></a>
            </th>
            <th>
                <a><?= __('Alertas') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?= __('Exportar produto') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Exportar Stock') ?></a>
            </th>
        </tr>
        <tr>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_name"
                        value="<?= $filters['filter_name'] ?>"
                >
            </th>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_reference"
                        value="<?= $filters['filter_reference'] ?>"
            </th>
            <th></th>
            <th></th>
            <th>
                <button type="submit" class="button button-primary">
                    <?= __('Pesquisar') ?>
                </button>

                <a href='<?= $currentAction ?>' class="button">
                    <?= __('Limpar') ?>
                </a>
            </th>
            <th></th>
            <th></th>
        </tr>
        </thead>

        <tbody>
        <?php if (!empty($rows) && is_array($rows)) : ?>
            <?php foreach ($rows as $row) : ?>
                <?= $row ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr class="text-center">
                <td colspan="100%">
                    <?= __('Não foram encontados produtos WooCommerce!') ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>

        <tfoot>
        <tr>
            <th>
                <a><?= __('Nome') ?></a>
            </th>
            <th>
                <a><?= __('Referência') ?></a>
            </th>
            <th>
                <a><?= __('Tipo') ?></a>
            </th>
            <th>
                <a><?= __('Alertas') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?= __('Exportar produto') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Exportar Stock') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Voltar') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?= __('Correr exportações') ?>
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

