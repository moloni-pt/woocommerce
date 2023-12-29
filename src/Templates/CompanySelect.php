<?php

if (!defined('ABSPATH')) {
    exit;
}

$hasValidCompany = false;
?>

<section id="moloni" class="moloni">
    <?php include MOLONI_TEMPLATE_DIR . '/assets/Fonts.php' ?>

    <div class="companies">
        <?php if (!empty($companies) && is_array($companies)) : ?>
            <div class="companies__title">
                <?= __("Selecione a empresa que pretende conectar com o WooCommerce") ?>
            </div>

            <div class="companies__list">
                <?php
                foreach ($companies as $company) {
                    include MOLONI_TEMPLATE_DIR . 'Blocks/CompanySelect/CompanyCard.php';
                }
                ?>
            </div>
        <?php else : ?>
            <?php include MOLONI_TEMPLATE_DIR . 'Blocks/CompanySelect/NoCompanies.php'; ?>
        <?php endif; ?>
    </div>

    <script>
        jQuery(document).ready(function () {

        });
    </script>
</section>
