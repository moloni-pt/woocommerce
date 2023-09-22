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
                $message ?? ''
            ?>
        </p>

        <a onclick="showMoloniErrors()" style="cursor: pointer;">
            <p><?= __("Clique aqui para mais informações") ?></p>
        </a>

        <div class="MoloniConsoleLogError" style="display: none;">
            <b><?= __("Dados") ?>: </b>

            <br>

            <pre>
                <?=
                    /** @var array $data */
                    json_encode($data ?? [], JSON_PRETTY_PRINT)
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
