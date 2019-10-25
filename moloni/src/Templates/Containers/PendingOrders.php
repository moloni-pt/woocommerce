<?php $orders = \Moloni\Controllers\PendingOrders::getAllAvailable(); ?>

<div class="wrap">

    <?php if (isset($document) && $document instanceof \Moloni\Controllers\Documents && $document->getError()) : ?>
        <?php $document->getError()->showError(); ?>
    <?php endif; ?>

    <h3>Aqui pode consultar todas as encomendas que tem por gerar</h3>
    <table class='wp-list-table widefat fixed striped posts'>
        <thead>
        <tr>
            <th><a><?= __("Encomenda") ?></a></th>
            <th><a><?= __("Cliente") ?></a></th>
            <th><a><?= __("Contribuinte") ?></a></th>
            <th><a><?= __("Total") ?></a></th>
            <th><a><?= __("Estado") ?></a></th>
            <th><a><?= __("Data de Pagamento") ?></a></th>
            <th style="width: 350px;"></th>
        </tr>
        </thead>

        <?php if (!empty($orders) && is_array($orders)) : ?>

            <!-- Lets draw a list of all the available orders -->
            <?php foreach ($orders as $order) : ?>
                <tr>
                    <td>
                        <a href=<?= admin_url('post.php?post=' . $order['id'] . '&action=edit') ?>>#<?= $order['number'] ?></a>
                    </td>
                    <td>
                        <?php
                        if (isset($order['info']['_billing_first_name']) && !empty($order['info']['_billing_first_name'])) {
                            echo $order['info']['_billing_first_name'] . " " . $order['info']['_billing_last_name'];
                        } else {
                            echo __("Desconhecido");
                        }

                        ?>
                    <td><?= (isset($order['info'][VAT_FIELD]) && !empty($order['info'][VAT_FIELD])) ? $order['info'][VAT_FIELD] : '999999990' ?></td>
                    <td><?= $order['info']['_order_total'] . $order['info']['_order_currency'] ?></td>
                    <td><?= $order['status'] ?></td>
                    <td><?= $order['info']['_completed_date'] ?></td>
                    <td class="order_status column-order_status" style="text-align: right">
                        <form action="<?= admin_url('admin.php') ?>">
                            <input type="hidden" name="page" value="moloni">
                            <input type="hidden" name="action" value="genInvoice">
                            <input type="hidden" name="id" value="<?= $order['id'] ?>">
                            <select name="document_type" style="margin-right: 5px">
                                <option value='invoices' <?= (DOCUMENT_TYPE == "invoices" ? "selected" : "") ?>>
                                    <?= __('Faturas') ?>
                                </option>

                                <option value='invoiceReceipts' <?= (DOCUMENT_TYPE == "invoiceReceipts" ? "selected" : "") ?>>
                                    <?= __("Factura/Recibo") ?>
                                </option>

                                <option value='simplifiedInvoices'<?= (DOCUMENT_TYPE == "simplifiedInvoices" ? "selected" : "") ?>>
                                    <?= __('Factura Simplificada') ?>
                                </option>

                                <option value='billsOfLading' <?= (DOCUMENT_TYPE == "billsOfLading" ? "selected" : "") ?>>
                                    <?= __('Guia de Transporte') ?>
                                </option>

                                <option value='purchaseOrder' <?= (DOCUMENT_TYPE == "purchaseOrder" ? "selected" : "") ?>>
                                    <?= __('Nota de Encomenda') ?>
                                </option>
                            </select>

                            <input type="submit"
                                   class="wp-core-ui button-primary"
                                   style="width: 80px; text-align: center; margin-right: 5px"
                                   value="<?= __('Gerar') ?>"
                            >


                            <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                               href="<?= admin_url('admin.php?page=moloni&action=remInvoice&id=' . $order['id']) ?>">
                                <?= __('Limpar') ?>
                            </a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php else : ?>
            <tr>
                <td colspan="7">
                    Não foram encontadas encomendas por gerar!
                </td>
            </tr>

        <?php endif; ?>

        <tfoot>
        <tr>
            <th><a><?= __("Encomenda") ?></a></th>
            <th><a><?= __("Cliente") ?></a></th>
            <th><a><?= __("Contribuinte") ?></a></th>
            <th><a><?= __("Total") ?></a></th>
            <th><a><?= __("Estado") ?></a></th>
            <th><a><?= __("Data de Pagamento") ?></a></th>
            <th></th>
        </tr>
        </tfoot>
    </table>
</div>
