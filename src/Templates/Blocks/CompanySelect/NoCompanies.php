<?php

use Moloni\Enums\Domains;

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="companies-invalid">
    <img src="<?= MOLONI_IMAGES_URL ?>no_companies.svg" width='150px' alt="Moloni">

    <div class="companies-invalid__title">
        <?= __('Não dispõe de nenhuma empresa válida para o uso do plugin') ?>
    </div>

    <div class="companies-invalid__message">
        <?= __('Por favor confirme se a sua conta tem acesso a uma empresa ativa e com um plano que lhe permita ter acesso aos plugins.') ?>
    </div>

    <div class="companies-invalid__help">
        <?= __('Saiba mais sobre os nossos planos em: ') ?>
        <a href="<?= Domains::PLANS ?>" target="_blank"><?= Domains::PLANS ?></a>
    </div>

    <button class="ml-button ml-button--primary" onclick="window.location.href = 'admin.php?page=moloni&action=logout'">
        <?= __('Voltar ao login') ?>
    </button>
</div>
