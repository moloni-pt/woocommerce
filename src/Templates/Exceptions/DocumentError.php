<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div>
    <div id="message" class="updated error is-dismissible">
        <p>
            <?= wp_kses_post($message ?? ''); ?>
        </p>
        <a onclick="showMoloniErrors()" style="cursor: pointer;">
            <p><?php esc_html_e("Clique aqui para mais informações") ?></p>
        </a>

        <div class="MoloniConsoleLogError" style="display: none;">
            <b><?php esc_html_e("Endpoint") ?>: </b> <?= esc_html($url ?? '') ?>
            <br>

            <b><?php esc_html_e("Resposta recebida") ?>: </b>
            <br/>
            <pre>
                <?=
                    /** @var array $received */
                    esc_html(print_r($received, true))
                ?>
            </pre>

            <b><?php esc_html_e("Dados enviados") ?>: </b>
            <br/>
            <pre>
                <?=
                    /** @var array $sent */
                    esc_html(print_r($sent, true))
                ?>
            </pre>
        </div>
    </div>
</div>

<script>
    function showMoloniErrors() {
        var errorConsole = document.getElementsByClassName("MoloniConsoleLogError");
        if (errorConsole.length > 0) {
            Array.prototype.forEach.call(errorConsole, function (element) {
                element.style['display'] = element.style['display'] === 'none' ? 'block' : 'none';
            });
        }
    }
</script>
