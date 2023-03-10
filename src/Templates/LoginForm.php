<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php if (isset($errorData) && !empty($errorData)): ?>
    <pre style="display: none;" id="curl_error_data">
            <?= print_r($errorData, true) ?>
        </pre>
<?php endif; ?>

<div id='formLogin'>
    <a href='https://moloni.pt' target='_BLANK'>
        <img src="<?= MOLONI_IMAGES_URL ?>logo.svg" width='300px' alt="Moloni">
    </a>
    <hr>

    <form id='formPerm' method='POST' action='<?= admin_url('admin.php?page=moloni') ?>'>
        <table>
            <tr>
                <td><label for='username'><?= __("Utilizador/Email") ?></label></td>
                <td><input id="username" type='text' name='user'></td>
            </tr>

            <tr>
                <td><label for='password'>Password</label></td>
                <td><input id="password" type='password' name='pass'></td>
            </tr>

            <?php if (isset($error) && $error): ?>
                <tr>
                    <td></td>
                    <td style='text-align: center;'><?= $error ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <td></td>
                <td>
                    <div>
                        <input type='submit' name='submit' value='<?= __("Entrar") ?>'>
                        <span class='goRight power'>
                            <a href="https://plugins.moloni.com/woocommerce" target="_blank">
                                <?= __("Duvidas no processo de instalação?") ?>
                            </a>
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </form>
</div>
