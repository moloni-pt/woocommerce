<?php
if (!defined('ABSPATH')) {
    exit;
}

use Moloni\Controllers\Logs;
use Moloni\Enums\LogLevel;

$logs = Logs::getAllAvailable();
?>

<div class="wrap">
    <h3><?= __('Aqui pode consultar todas os registos do plugin') ?></h3>

    <div class="tablenav top">
        <div class="tablenav-pages">
            <?= Logs::getPagination() ?>
        </div>
    </div>

    <table class='wp-list-table widefat fixed striped posts'>
        <thead>
        <tr>
            <th><a><?= __('Data') ?></a></th>
            <th><a><?= __('Nível') ?></a></th>
            <th><a><?= __('Mensagem') ?></a></th>
            <th><a><?= __('Contexto') ?></a></th>
        </tr>
        </thead>

        <?php if (!empty($logs) && is_array($logs)) : ?>
            <?php foreach ($logs as $log) : ?>
                <tr>
                    <td>
                        <?= $log['created_at'] ?>
                    </td>
                    <td>
                        <?= LogLevel::getTranslation($log['log_level'] ?? '') ?>
                    </td>
                    <td>
                        <?= $log['message'] ?>
                    </td>
                    <td>
                        <?php $logContext = htmlspecialchars($log['context']) ?>

                        <button type="button" class="button action" onclick="Moloni.Logs.openContextDialog(<?= $logContext ?>)">
                            <?= __("Ver") ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="4">
                    <?= __('Não foram encontados registos!') ?>
                </td>
            </tr>
        <?php endif; ?>

        <tfoot>
        <tr>
            <th><a><?= __('Data') ?></a></th>
            <th><a><?= __('Nível') ?></a></th>
            <th><a><?= __('Mensagem') ?></a></th>
            <th><a><?= __('Contexto') ?></a></th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="alignleft actions">
            <a class="button button-primary"
               href='<?= admin_url('admin.php?page=moloni&tab=logs&action=remLogs') ?>'>
                <?= __('Apagar registos com mais de 1 semana') ?>
            </a>
        </div>

        <div class="tablenav-pages">
            <?= Logs::getPagination() ?>
        </div>
    </div>
</div>

<div id="logs-context-modal" class="modal" style="display: none">
    <h2>
        <?= __('Contexto do registo') ?>
    </h2>

    <pre id="logs-context-modal-content"></pre>

    <button type="button" class="button action">
        <?= __("Descarregar") ?>
    </button>
</div>

<script>
    Moloni.Logs.init();
</script>
