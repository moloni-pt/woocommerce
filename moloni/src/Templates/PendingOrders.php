<?php $orders = \Moloni\Controllers\PendingOrders::getAllAvailable(); ?>

<div id="content" class="wrap">
    <h3>Aqui pode consultar todas as encomendas que tem por gerar</h3>

    <table class='wp-list-table widefat fixed striped posts'>
        <thead>
        <tr>
            <th><a><?= __("Number") ?></a></th>
            <th><a><?= __("Customer") ?></a></th>
            <th><a><?= __("Vat Number") ?></a></th>
            <th><a><?= __("Total") ?></a></th>
            <th><a><?= __("Status") ?></a></th>
            <th><a><?= __("Payment") ?></a></th>
            <th></th>
        </tr>
        </thead>

        <?php foreach ($orders as $order) : ?>
            <tr>
                <td><a href='post.php?post=<?= $order['id'] ?>&action=edit'>#<?= $order['id'] ?></a></td>
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
                <td style="width: 300px; text-align: right">
                    <a class="wp-core-ui button-primary" style="width: 80px; text-align: center; margin-right: 5px"
                       href="admin.php?page=moloni&action=genInvoice&id=<?= $order['id'] ?>">
                        <?= __('Gerar') ?>
                    </a>

                    <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                       href="admin.php?page=moloni&action=remInvoice&id=<?= $order['id'] ?>&delete=permission">
                        <?= __('Limpar') ?>
                    </a>
                </td>
            </tr>

        <?php endforeach; ?>


        <tfoot>
        <tr>
            <th><a><?= __("Number") ?></a></th>
            <th><a><?= __("Customer") ?></a></th>
            <th><a><?= __("Vat Number") ?></a></th>
            <th><a><?= __("Total") ?></a></th>
            <th><a><?= __("Status") ?></a></th>
            <th><a><?= __("Payment") ?></a></th>
            <th></th>
        </tr>
        </tfoot>
    </table>
</div>
