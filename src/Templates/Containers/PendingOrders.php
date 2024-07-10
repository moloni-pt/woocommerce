<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php

use Moloni\Enums\DocumentTypes;
use Moloni\Models\PendingOrders;

/** @var WC_Order[] $orders */
$orders = PendingOrders::getAllAvailable();
$pagination = PendingOrders::getPagination();
?>

<h3>
    <?php esc_html_e('Aqui pode consultar todas as encomendas que tem por gerar') ?>
</h3>

<div class="tablenav top">
    <div class="alignleft actions bulkactions">
        <label for="bulk-action-selector-top" class="screen-reader-text">
            <?php esc_html_e('Seleccionar acção por lotes') ?>
        </label>
        <select name="action" id="bulk-action-selector-top">
            <option value="-1"><?php esc_html_e('Ações por lotes') ?></option>
            <option value="bulkGenInvoice"><?php esc_html_e('Gerar documentos') ?></option>
            <option value="bulkDiscardOrder"><?php esc_html_e('Descartar encomendas') ?></option>
        </select>
        <input type="submit" id="doAction" class="button action" value="<?php esc_html_e('Correr') ?>">
    </div>

    <div class="tablenav-pages">
        <?= wp_kses_post($pagination) ?>
    </div>
</div>

<table class='wp-list-table widefat striped posts'>
    <thead>
    <tr>
        <td class="manage-column column-cb check-column">
            <label for="moloni-pending-orders-select-all" class="screen-reader-text"></label>
            <input id="moloni-pending-orders-select-all" class="moloni-pending-orders-select-all" type="checkbox">
        </td>
        <th><a><?php esc_html_e('Encomenda') ?></a></th>
        <th><a><?php esc_html_e('Cliente') ?></a></th>
        <th><a><?php esc_html_e('Contribuinte') ?></a></th>
        <th><a><?php esc_html_e('Total') ?></a></th>
        <th><a><?php esc_html_e('Estado') ?></a></th>
        <th><a><?php esc_html_e('Data de Pagamento') ?></a></th>
        <th style="width: 350px;"></th>
    </tr>
    </thead>

    <?php if (!empty($orders) && is_array($orders)) : ?>

        <!-- Let's draw a list of all the available orders -->
        <?php foreach ($orders as $order) : ?>
            <tr id="moloni-pending-order-row-<?= esc_html($order->get_id()) ?>">
                <td class="">
                    <label for="moloni-pending-order-<?= esc_html($order->get_id()) ?>" class="screen-reader-text"></label>
                    <input id="moloni-pending-order-<?= esc_html($order->get_id()) ?>" type="checkbox"
                           value="<?= esc_html($order->get_id()) ?>">
                </td>
                <td>
                    <a target="_blank" href=<?= esc_url($order->get_edit_order_url()) ?>>
                        #<?= esc_html($order->get_order_number()) ?>
                    </a>
                </td>
                <td>
                    <?php
                    if (!empty($order->get_billing_first_name())) {
                        echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                    } else {
                        esc_html_e('Desconhecido');
                    }
                    ?>
                <td>
                    <?php
                    $vat = '';

                    if (defined('VAT_FIELD')) {
                        $meta = $order->get_meta(VAT_FIELD);

                        $vat = $meta;
                    }

                    echo esc_html(empty($vat) ? '999999990' : $vat);
                    ?>
                </td>
                <td>
                    <?= esc_html($order->get_total() . $order->get_currency()) ?>
                </td>
                <td>
                    <?php
                    $availableStatus = wc_get_order_statuses();
                    $needle = 'wc-' . $order->get_status();

                    if (isset($availableStatus[$needle])) {
                        echo esc_html($availableStatus[$needle]);
                    } else {
                       echo esc_html($needle);
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (!empty($order->get_date_paid())) {
                        echo esc_html(date('Y-m-d H:i:s', strtotime($order->get_date_paid())));
                    } else {
                        echo 'n/a';
                    }
                    ?>
                </td>
                <td class="order_status column-order_status" style="text-align: right">
                    <form action="<?= esc_url(admin_url('admin.php')) ?>">
                        <input type="hidden" name="page" value="moloni">
                        <input type="hidden" name="action" value="genInvoice">
                        <input type="hidden" name="id" value="<?= esc_html($order->get_id()) ?>">

                        <?php
                        if (defined('DOCUMENT_TYPE')) {
                            $documentType = DOCUMENT_TYPE;
                        } else {
                            $documentType = 'invoices';
                        }
                        ?>

                        <select name="document_type" style="margin-right: 5px">
                            <?php foreach (DocumentTypes::getDocumentTypeForRender() as $id => $name) : ?>
                                <option value='<?= esc_html($id) ?>' <?= ($documentType === $id ? 'selected' : '') ?>>
                                    <?php esc_html_e($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="submit"
                               class="wp-core-ui button-primary"
                               style="width: 80px; text-align: center; margin-right: 5px"
                               value="<?php esc_html_e('Gerar') ?>">

                        <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                           href="<?= esc_url(admin_url('admin.php?page=moloni&action=remInvoice&id=' . $order->get_id())) ?>">
                            <?php esc_html_e('Descartar') ?>
                        </a>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>

    <?php else : ?>
        <tr>
            <td colspan="8">
                <?php esc_html_e('Não foram encontadas encomendas por gerar!') ?>
            </td>
        </tr>
    <?php endif; ?>

    <tfoot>
    <tr>
        <td class="manage-column column-cb check-column">
            <label for="moloni-pending-orders-select-all-bottom" class="screen-reader-text"></label>
            <input id="moloni-pending-orders-select-all-bottom" class="moloni-pending-orders-select-all"
                   type="checkbox">
        </td>

        <th><a><?php esc_html_e('Encomenda') ?></a></th>
        <th><a><?php esc_html_e('Cliente') ?></a></th>
        <th><a><?php esc_html_e('Contribuinte') ?></a></th>
        <th><a><?php esc_html_e('Total') ?></a></th>
        <th><a><?php esc_html_e('Estado') ?></a></th>
        <th><a><?php esc_html_e('Data de Pagamento') ?></a></th>
        <th></th>
    </tr>
    </tfoot>
</table>

<div class="tablenav bottom">
    <div class="tablenav-pages">
        <?= wp_kses_post($pagination) ?>
    </div>
</div>

<?php include MOLONI_TEMPLATE_DIR . 'Modals/PendingOrders/BulkActionModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.OrdersBulkAction();
    });
</script>
