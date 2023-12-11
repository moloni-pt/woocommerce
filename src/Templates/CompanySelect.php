<?php

if (!defined('ABSPATH')) {
    exit;
}

$hasValidCompany = false;
?>

<section id="moloni" class="moloni">
    <div class="companies">
        <?php if (!empty($companies) && is_array($companies)) : ?>
            <div class="companies__title">
                <h2>
                    <?= __("Bem vindo! Aqui pode seleccionar qual a empresa que pretende ligar com o WooCoommerce") ?>
                </h2>
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
