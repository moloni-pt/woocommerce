<?php
if (!defined('ABSPATH')) {
    exit;
}

use Moloni\Enums\LogLevel;
use Moloni\Models\Logs;

$logs = Logs::getAllAvailable();
?>

<div class="wrap">
    <h3><?= __('Aqui pode consultar todas os registos do plugin') ?></h3>

    <div class="tablenav top">
        <div class="tablenav-pages">
            <?= Logs::getPagination() ?>
        </div>
    </div>

    <form method="post" action='<?= admin_url('admin.php?page=moloni&tab=logs') ?>'>
        <table class='wp-list-table widefat fixed striped posts'>
            <thead>
            <tr>
                <th><a><?= __('Data') ?></a></th>
                <th><a><?= __('Nível') ?></a></th>
                <th><a><?= __('Mensagem') ?></a></th>
                <th><a><?= __('Contexto') ?></a></th>
            </tr>
            <tr>
                <th>
                    <input
                            type="date"
                            class="inputOut ml-0"
                            name="filter_date"
                            value="<?= $_GET['filter_date'] ?? $_POST['filter_date'] ?? '' ?>"
                    >
                </th>
                <th>
                    <?php $options = LogLevel::getForRender() ?>

                    <select name="filter_level">
                        <?php $filterLevel = $_GET['filter_level'] ?? $_POST['filter_level'] ?? '' ?>

                        <option value='' selected><?=
                            __('Escolha uma opção') ?>
                        </option>

                        <?php foreach ($options as $option) : ?>
                            <option
                                    value='<?= $option['value'] ?>'
                                <?= $filterLevel === $option['value'] ? 'selected' : '' ?>
                            >
                                <?= $option['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <input
                            type="text"
                            class="inputOut ml-0"
                            name="filter_message"
                            value="<?= $_GET['filter_message'] ?? $_POST['filter_message'] ?? '' ?>"
                    >
                </th>
                <th>
                    <button type="submit" name="submit" id="submit" class="button button-primary">
                        <?= __('Pesquisar') ?>
                    </button>
                </th>
            </tr>
            </thead>

            <?php if (!empty($logs) && is_array($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td>
                            <?= $log['created_at'] ?>
                        </td>
                        <td>
                            <?php
                            $logLevel = $log['log_level'] ?? '';
                            ?>

                            <div class="chip <?= LogLevel::getClass($logLevel) ?>">
                                <?= LogLevel::getTranslation($logLevel) ?>
                            </div>
                        </td>
                        <td>
                            <?= $log['message'] ?>
                        </td>
                        <td>
                            <?php $showOverlayButton = true ?>

                            <?php if ($logLevel === LogLevel::DEBUG) : ?>
                                <?php $payload = json_decode($log['context'], true) ?>

                                <?php if (isset($payload['link'])) : ?>
                                    <a type="button"
                                       download="<?= $log['message'] ?>.log"
                                       class="button action"
                                       href="<?= $payload['link'] ?>">
                                        <?= __("Descarregar") ?>
                                    </a>

                                    <?php $showOverlayButton = false ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($showOverlayButton) : ?>
                                <?php $logContext = htmlspecialchars($log['context']) ?>

                                <button type="button" class="button action" onclick="Moloni.Logs.openContextDialog(<?= $logContext ?>)">
                                    <?= __("Ver") ?>
                                </button>
                            <?php endif; ?>
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
    </form>

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

<?php include MOLONI_TEMPLATE_DIR . 'Modals/Logs/LogsContextModal.php'; ?>

<script>
    Moloni.Logs.init();
</script>
