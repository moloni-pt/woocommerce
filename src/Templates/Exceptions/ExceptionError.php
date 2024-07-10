<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div>
    <div id="message" class="updated error is-dismissible">
        <p>
            <?=
            /** @var string $message */
                wp_kses_post($message ?? '');
            ?>
        </p>

        <a onclick="showMoloniErrors()" style="cursor: pointer;">
            <p><?php esc_html_e("Clique aqui para mais informações") ?></p>
        </a>

        <div class="MoloniConsoleLogError" style="display: none;">
            <b><?php esc_html_e("Dados") ?>: </b>

            <br>

            <pre>
                <?=
                    /** @var array $data */
                    wp_json_encode($data ?? [], JSON_PRETTY_PRINT)
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
