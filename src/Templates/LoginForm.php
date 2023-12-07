<?php

use Moloni\Enums\Domains;

if (!defined('ABSPATH')) {
    exit;
}
?>

<section id="moloni" class="moloni">
    <?php if (!empty($errorData)): ?>
        <pre style="display: none;" id="curl_error_data">
            <?= print_r($errorData, true) ?>
        </pre>
    <?php endif; ?>

    <div class="login login__wrapper">
        <form class="login-form" method='POST' action='<?= admin_url('admin.php?page=moloni') ?>'>
            <div class="login__card">
                <div class="login__image">
                    <a href="<?= Domains::HOMEPAGE ?>" target="_blank">
                        <img src="<?= MOLONI_IMAGES_URL ?>logo.svg" width="140px" height="24px" alt="Logo">
                    </a>
                </div>

                <div class="login__inputs mt-2">
                    <label for='username'>
                        <?= __("Utilizador/Email") ?>
                    </label>
                    <input id="username" type='text' name='user'>
                </div>

                <div class="login__inputs mt-2">
                    <label for='password'>
                        <?= __("Password") ?>
                    </label>
                    <input id="password" type='password' name='pass'>
                </div>

                <?php if (isset($error) && $error): ?>
                    <div class="login__error mt-4">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <button id="login_button" class="ml-button ml-button--primary w-full my-4" type="submit" disabled>
                    <?= __("Entrar") ?>
                </button>

                <div class="login__divider">
                    <span></span>
                    <div>
                        <?= __('Não tem conta?') ?>
                    </div>
                    <span></span>
                </div>

                <a type="button" class="ml-button ml-button--secondary w-full mt-4" target="_blank"
                   href="<?= Domains::REGISTER ?>">
                    <?= __("Criar conta") ?>
                </a>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function () {
            Moloni.Login.init();
        });
    </script>
</section>
