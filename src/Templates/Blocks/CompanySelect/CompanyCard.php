<?php

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? [];
?>

<div class="companies__card">
    <div class="companies__card-content">
        <div class="companies__card-header">
            <div class="companies__card-accent"></div>
            <div>
                <?= esc_html($company["name"]) ?>
            </div>
        </div>

        <div class="companies__card-divider"></div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?php esc_html_e("Morada") ?>
            </div>
            <div class="companies__card-text">
                <?= esc_html($company["address"]) ?>
            </div>
            <div class="companies__card-text">
                <?= esc_html($company["zip_code"]) ?>
            </div>
        </div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?php esc_html_e("Contribuinte") ?>
            </div>
            <div class="companies__card-text">
                <?= esc_html($company["vat"]) ?>
            </div>
        </div>
    </div>

    <button class="ml-button ml-button--primary w-full"
            onclick="window.location.href = 'admin.php?page=moloni&company_id=<?= (int)$company["company_id"] ?>'">
        <?php esc_html_e('Escolher empresa') ?>
    </button>
</div>
