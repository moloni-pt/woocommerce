<div style="margin-top: 50px">
    <div id="message" class="updated error is-dismissible">
        <p><?= $message ?></p>
        <a onclick="showMoloniErrors()" style="cursor: pointer;">
            <p><?= __("Clique aqui para mais informações") ?></p>
        </a>

        <div class="MoloniConsoleLogError" style="display: none;">
            <p>
                <b><?= __("Endpoint") ?>: </b> <?= $url ?>
            </p>
            <b><?= __("Resposta recebida: ") ?></b>
            <br/>
            <pre><?= /** @var array $received */
                print_r($received, true) ?>
            </pre>

            <b><?= __("Dados enviados: ") ?></b>
            <br/>
            <pre><?= /** @var array $sent */
                print_r($sent, true) ?>
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