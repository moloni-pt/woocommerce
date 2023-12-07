<?php

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? [];
?>

<div class="companies__card">
    <div class="companies__card-title">
        <?= $company["name"] ?>
    </div>

    <div class="companies__card-address">
        <b>
            <?= __("Morada") ?>
        </b>
        <div>
            <?= $company["address"] ?>
        </div>
        <div>
            <?= $company["zip_code"] ?>
        </div>
    </div>

    <div class="companies__card-vat">
        <b>
            <?= __("Contribuinte") ?>
        </b>
        <div>
            <?= $company["vat"] ?>
        </div>
    </div>

    <button class="ml-button ml-button--primary w-full"
            onclick="window.location.href = 'admin.php?page=moloni&action=logout'">
        <?= __('Escolher empresa') ?>
    </button>
</div>
