<?php

use Moloni\Enums\Domains;

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="companies-invalid">
    <img src="<?= esc_url(MOLONI_IMAGES_URL) ?>no_companies.svg" width='150px' alt="Moloni">

    <div class="companies-invalid__title">
        <?php esc_html_e('Não dispõe de nenhuma empresa válida para o uso do plugin') ?>
    </div>

    <div class="companies-invalid__message">
        <?php esc_html_e('Por favor confirme se a sua conta tem acesso a uma empresa ativa e com um plano que lhe permita ter acesso aos plugins.') ?>
    </div>

    <div class="companies-invalid__help">
        <?php esc_html_e('Saiba mais sobre os nossos planos em: ') ?>
        <a href="<?= esc_url(Domains::PLANS) ?>" target="_blank"><?= esc_url(Domains::PLANS) ?></a>
    </div>

    <button class="ml-button ml-button--primary" onclick="window.location.href = 'admin.php?page=moloni&action=logout'">
        <?php esc_html_e('Voltar ao login') ?>
    </button>
</div>
