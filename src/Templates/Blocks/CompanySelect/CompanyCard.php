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
                <?= $company["name"] ?>
            </div>
        </div>

        <div class="companies__card-divider"></div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?= __("Morada") ?>
            </div>
            <div class="companies__card-text">
                <?= $company["address"] ?>
            </div>
            <div class="companies__card-text">
                <?= $company["zip_code"] ?>
            </div>
        </div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?= __("Contribuinte") ?>
            </div>
            <div class="companies__card-text">
                <?= $company["vat"] ?>
            </div>
        </div>
    </div>

    <button class="ml-button ml-button--primary w-full"
            onclick="window.location.href = 'admin.php?page=moloni&company_id=<?= $company["company_id"] ?>'">
        <?= __('Escolher empresa') ?>
    </button>
</div>
