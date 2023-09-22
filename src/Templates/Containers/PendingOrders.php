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
?>

<div class="wrap">
    <h3><?= __('Aqui pode consultar todas as encomendas que tem por gerar') ?></h3>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text">Seleccionar acção por lotes</label><select
                    name="action" id="bulk-action-selector-top">
                <option value="-1"><?= __('Ações por lotes') ?></option>
                <option value="bulkGenInvoice"><?= __('Gerar documentos') ?></option>
                <option value="bulkDiscardOrder"><?= __('Descartar encomendas') ?></option>
            </select>
            <input type="submit" id="doAction" class="button action" value="<?= __('Correr') ?>">
        </div>

        <div class="tablenav-pages">
            <?= PendingOrders::getPagination() ?>
        </div>
    </div>

    <table class='wp-list-table widefat fixed striped posts'>
        <thead>
        <tr>
            <td class="manage-column column-cb check-column">
                <label for="moloni-pending-orders-select-all" class="screen-reader-text"></label>
                <input id="moloni-pending-orders-select-all" class="moloni-pending-orders-select-all" type="checkbox">
            </td>
            <th><a><?= __('Encomenda') ?></a></th>
            <th><a><?= __('Cliente') ?></a></th>
            <th><a><?= __('Contribuinte') ?></a></th>
            <th><a><?= __('Total') ?></a></th>
            <th><a><?= __('Estado') ?></a></th>
            <th><a><?= __('Data de Pagamento') ?></a></th>
            <th style="width: 350px;"></th>
        </tr>
        </thead>

        <?php if (!empty($orders) && is_array($orders)) : ?>

            <!-- Let's draw a list of all the available orders -->
            <?php foreach ($orders as $order) : ?>
                <tr id="moloni-pending-order-row-<?= $order->get_id() ?>">
                    <td class="">
                        <label for="moloni-pending-order-<?= $order->get_id() ?>" class="screen-reader-text"></label>
                        <input id="moloni-pending-order-<?= $order->get_id() ?>" type="checkbox"
                               value="<?= $order->get_id() ?>">
                    </td>
                    <td>
                        <a target="_blank" href=<?= $order->get_edit_order_url() ?>>#<?= $order->get_order_number() ?></a>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_billing_first_name())) {
                            echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        } else {
                            echo __('Desconhecido');
                        }
                        ?>
                    <td>
                        <?php
                        $vat = '';

                        if (defined('VAT_FIELD')) {
                            $meta = $order->get_meta(VAT_FIELD);

                            $vat = $meta;
                        }

                        echo empty($vat) ? '999999990' : $vat;
                        ?>
                    </td>
                    <td><?= $order->get_total() . $order->get_currency() ?></td>
                    <td>
                        <?php
                        $availableStatus = wc_get_order_statuses();
                        $needle = 'wc-' . $order->get_status();

                        if (isset($availableStatus[$needle])) {
                            echo $availableStatus[$needle];
                        } else {
                           echo $needle;
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_date_paid())) {
                            echo date('Y-m-d H:i:s', strtotime($order->get_date_paid()));
                        } else {
                            echo 'n/a';
                        }
                        ?>
                    </td>
                    <td class="order_status column-order_status" style="text-align: right">
                        <form action="<?= admin_url('admin.php') ?>">
                            <input type="hidden" name="page" value="moloni">
                            <input type="hidden" name="action" value="genInvoice">
                            <input type="hidden" name="id" value="<?= $order->get_id() ?>">

                            <?php
                            if (defined('DOCUMENT_TYPE')) {
                                $documentType = DOCUMENT_TYPE;
                            } else {
                                $documentType = 'invoices';
                            }
                            ?>

                            <select name="document_type" style="margin-right: 5px">
                                <?php foreach (DocumentTypes::getDocumentTypeForRender() as $id => $name) : ?>
                                    <option value='<?= $id ?>' <?= ($documentType === $id ? 'selected' : '') ?>>
                                        <?= __($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="submit"
                                   class="wp-core-ui button-primary"
                                   style="width: 80px; text-align: center; margin-right: 5px"
                                   value="<?= __('Gerar') ?>">

                            <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                               href="<?= admin_url('admin.php?page=moloni&action=remInvoice&id=' . $order->get_id()) ?>">
                                <?= __('Descartar') ?>
                            </a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php else : ?>
            <tr>
                <td colspan="8">
                    <?= __('Não foram encontadas encomendas por gerar!') ?>
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

            <th><a><?= __('Encomenda') ?></a></th>
            <th><a><?= __('Cliente') ?></a></th>
            <th><a><?= __('Contribuinte') ?></a></th>
            <th><a><?= __('Total') ?></a></th>
            <th><a><?= __('Estado') ?></a></th>
            <th><a><?= __('Data de Pagamento') ?></a></th>
            <th></th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?= PendingOrders::getPagination() ?>
        </div>
    </div>
</div>

<?php include MOLONI_TEMPLATE_DIR . 'Modals/PendingOrders/BulkActionModal.php'; ?>

