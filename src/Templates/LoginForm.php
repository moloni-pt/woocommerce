<?php

use Moloni\Enums\Domains;

if (!defined('ABSPATH')) {
    exit;
}
?>

<section id="moloni" class="moloni">
    <?php include MOLONI_DIR . '/assets/icons/plugin.svg' ?>
    <?php include MOLONI_TEMPLATE_DIR . '/assets/Fonts.php' ?>

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
                        <img src="<?= MOLONI_IMAGES_URL ?>logo.svg" width="186px" height="32px" alt="Logo">
                    </a>
                </div>

                <div class="login__title">
                    <?= __("Inicie sessão na sua conta") ?> <span>Moloni</span>
                </div>

                <div class="login__error">
                    <?php if (isset($error) && $error): ?>
                        <div class="ml-alert ml-alert--danger-light">
                            <svg>
                                <use xlink:href="#ic_notices_important_warning"></use>
                            </svg>

                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="login__inputs">
                    <div class="ml-input-text <?= isset($error) && $error ? 'ml-input-text--with-error' : '' ?>">
                        <label for='username'>
                            <?= __("E-mail") ?>
                        </label>
                        <input id="username" type='text' name='user'>
                    </div>

                    <div class="ml-input-text <?= isset($error) && $error ? 'ml-input-text--with-error' : '' ?>">
                        <label for='password'>
                            <?= __("Palavra-passe") ?>
                        </label>
                        <input id="password" type='password' name='pass'>
                    </div>
                </div>

                <div class="login__help">
                    <a href="<?= Domains::LANDINGPAGE ?>" target="_blank">
                        <?= __("Guia de instalação.") ?>
                    </a>
                </div>

                <div class="login__button">
                    <button class="ml-button ml-button--primary w-full" id="login_button" type="submit" disabled>
                        <?= __("Iniciar sessão") ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function () {
            Moloni.Login.init();
        });
    </script>
</section>
