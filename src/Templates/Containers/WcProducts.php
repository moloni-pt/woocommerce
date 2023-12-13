<?php

use Moloni\Exceptions\APIExeption;
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

<form method="post" action='<?= $currentAction ?>'>
    <div class="tablenav top">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Voltar') ?>
        </a>

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
            <th>
                <a><?= __('Ações') ?></a>
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
            <th>
                ---
            </th>
            <th>
                ---
            </th>
            <th>
                <button type="submit" class="button button-primary">
                    <?= __('Pesquisar') ?>
                </button>

                <a href='<?= $currentAction ?>' class="button">
                    <?= __('Limpar') ?>
                </a>
            </th>
        </tr>
        </thead>

        <tbody>
        <?php if (!empty($rows) && is_array($rows)) : ?>
            <?php foreach ($rows as $row) : ?>
                <?php include MOLONI_TEMPLATE_DIR . 'Blocks/WcProducts/ProductRow.php'; ?>
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
            <th>
                <a><?= __('Ações') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Voltar') ?>
        </a>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>
</form>

<script>
    jQuery(document).ready(function () {
        Moloni.WcProducts.init("<?= $currentAction ?>");
    });
</script>

