<?php
if (!defined('ABSPATH')) {
    exit;
}

?>

<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">
    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('Forçar sincronização de stocks') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('Sincronizar os stocks de todos os produtos usados nos últimos 7 dias') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= esc_url(admin_url('admin.php?page=moloni&tab=tools&action=syncStocks&since=' . gmdate('Y-m-d', strtotime("-1 week")))) ?>'
               class="button button-large"
            >
                <?php esc_html_e('Forçar sincronização de stocks') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('Listar produtos Moloni') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('Listar todos os produtos na empresa Moloni e importar dados para a sua loja WooCommerce') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= esc_url(admin_url('admin.php?page=moloni&tab=moloniProductsList')) ?>'
               class="button button-large"
            >
                <?php esc_html_e('Ver produtos Moloni') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('Listar produtos WooCommerce') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('Listar todos os produtos na loja WooCommerce e exportar dados para a sua empresa Moloni') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= esc_url(admin_url('admin.php?page=moloni&tab=wcProductsList')) ?>'
               class="button button-large"
            >
                <?php esc_html_e('Ver produtos WooCommerce') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('Limpar encomendas pendentes') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('Remover todas as encomendas da listagem de encomendas') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= esc_url(admin_url('admin.php?page=moloni&tab=tools&action=remInvoiceAll')) ?>'
               class="button button-large"
            >
                <?php esc_html_e('Limpar encomendas pendentes') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('Sair da empresa') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('Iremos manter os dados referentes aos documentos já emitidos') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= esc_url(admin_url('admin.php?page=moloni&tab=tools&action=logout')) ?>'
               class="button button-large button-primary"
            >
                <?php esc_html_e('Sair da empresa') ?>
            </a>
        </td>
    </tr>
    </tbody>
</table>
