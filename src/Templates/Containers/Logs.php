<?php
if (!defined('ABSPATH')) {
    exit;
}

use Moloni\Enums\LogLevel;
use Moloni\Models\Logs;

$logs = Logs::getAllAvailable();
$pagination = Logs::getPagination();

$logsContext = [];
?>

<h3><?php esc_html_e('Aqui pode consultar todas os registos do plugin') ?></h3>

<div class="tablenav top">
    <div class="tablenav-pages">
        <?= $pagination ?>
    </div>
</div>

<form method="post" action='<?= esc_url(admin_url('admin.php?page=moloni&tab=logs')) ?>'>
    <table class='wp-list-table widefat striped posts'>
        <thead>
        <tr>
            <th><a><?php esc_html_e('Data') ?></a></th>
            <th><a><?php esc_html_e('Nível') ?></a></th>
            <th><a><?php esc_html_e('Mensagem') ?></a></th>
            <th><a><?php esc_html_e('Contexto') ?></a></th>
            <th></th>
        </tr>
        <tr>
            <th>
                <input
                        type="date"
                        class="inputOut ml-0"
                        name="filter_date"
                        value="<?= esc_html($_GET['filter_date'] ?? $_POST['filter_date'] ?? '') ?>"
                >
            </th>
            <th>
                <?php $options = LogLevel::getForRender() ?>

                <select name="filter_level">
                    <?php $filterLevel = esc_html($_GET['filter_level'] ?? $_POST['filter_level'] ?? '') ?>

                    <option value='' selected>
                        <?php esc_html_e('Escolha uma opção') ?>
                    </option>

                    <?php foreach ($options as $option) : ?>
                        <option
                                value='<?= esc_html($option['value']) ?>'
                            <?= $filterLevel === $option['value'] ? 'selected' : '' ?>
                        >
                            <?= esc_html($option['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </th>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_message"
                        value="<?= esc_html($_GET['filter_message'] ?? $_POST['filter_message'] ?? '') ?>"
                >
            </th>
            <th>
                <input
                        type="text"
                        class="ml-0"
                        name="filter_context"
                        value="<?= esc_html($_GET['filter_context'] ?? $_POST['filter_context'] ?? '') ?>"
                >
            </th>
            <th>
                <button type="submit" name="submit" id="submit" class="button button-primary">
                    <?php esc_html_e('Pesquisar') ?>
                </button>
            </th>
        </tr>
        </thead>

        <?php if (!empty($logs) && is_array($logs)) : ?>
            <?php foreach ($logs as $log) : ?>
                <tr>
                    <td>
                        <?= esc_html($log['created_at']) ?>
                    </td>
                    <td>
                        <?php
                        $logLevel = $log['log_level'] ?? '';
                        ?>

                        <div class="chip <?= esc_html(LogLevel::getClass($logLevel)) ?>">
                            <?= esc_html(LogLevel::getTranslation($logLevel)) ?>
                        </div>
                    </td>
                    <td>
                        <?= esc_html($log['message']) ?>
                    </td>
                    <td colspan="2">
                        <?php $showOverlayButton = true ?>

                        <?php if ($logLevel === LogLevel::DEBUG) : ?>
                            <?php $payload = json_decode($log['context'], true) ?>

                            <?php if (isset($payload['link'])) : ?>
                                <a type="button"
                                   download="<?= esc_html($log['message']) ?>.log"
                                   class="button action"
                                   href="<?= esc_url($payload['link']) ?>">
                                    <?php esc_html_e("Descarregar") ?>
                                </a>

                                <?php $showOverlayButton = false ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($showOverlayButton) : ?>
                            <?php $logsContext[$log['id']] = $log['context'] ?>

                            <button type="button" class="button action log_button" data-log-id="<?= esc_html($log['id']) ?>">
                                <?php esc_html_e("Ver") ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="4">
                    <?php esc_html_e('Não foram encontados registos!') ?>
                </td>
            </tr>
        <?php endif; ?>

        <tfoot>
        <tr>
            <th><a><?php esc_html_e('Data') ?></a></th>
            <th><a><?php esc_html_e('Nível') ?></a></th>
            <th><a><?php esc_html_e('Mensagem') ?></a></th>
            <th><a><?php esc_html_e('Contexto') ?></a></th>
        </tr>
        </tfoot>
    </table>
</form>

<div class="tablenav bottom">
    <div class="alignleft actions">
        <a class="button button-primary"
           href='<?= esc_url(admin_url('admin.php?page=moloni&tab=logs&action=remLogs')) ?>'>
            <?php esc_html_e('Apagar registos com mais de 1 semana') ?>
        </a>
    </div>

    <div class="tablenav-pages">
        <?= $pagination ?>
    </div>
</div>

<?php include MOLONI_TEMPLATE_DIR . 'Modals/Logs/LogsContextModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.Logs.init(<?= wp_json_encode($logsContext) ?>);
    });
</script>
