<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php use Moloni\Log; ?>

<br>
<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">
    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Forçar sincronização de stocks') ?></strong>
            <p class='description'><?= __('Sincronizar os stocks de todos os artigos usados nos últimos 7 dias') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large"
               href='<?= admin_url('admin.php?page=moloni&tab=tools&action=syncStocks&since=' . gmdate('Y-m-d', strtotime("-1 week"))) ?>'>
                <?= __('Forçar sincronização de stocks') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Limpar encomendas pendentes') ?></strong>
            <p class='description'><?= __('Remover todas as encomendas da listagem de encomendas') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large"
               href='<?= admin_url('admin.php?page=moloni&tab=tools&action=remInvoiceAll') ?>'>
                <?= __('Limpar encomendas pendentes') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Sair da empresa') ?></strong>
            <p class='description'><?= __('Iremos manter os dados referentes aos documentos já emitidos') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large button-primary"
               href='<?= admin_url('admin.php?page=moloni&tab=tools&action=logout') ?>'>
                <?= __('Sair da empresa') ?>
            </a>
        </td>
    </tr>
    </tbody>
</table>
