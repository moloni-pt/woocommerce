<?php

use Moloni\Enums\CompositionTypes;

if (!defined('ABSPATH')) {
    exit;
}

$row = $row ?? [];
?>

<tr class="product__row"
    data-wc-id="<?= $row['wc_product_id'] ?? 0 ?>"
    data-moloni-id="<?= $row['moloni_product_id'] ?? 0 ?>"
>
    <td>
        <?= $row['wc_product_object']->get_name(); ?>
    </td>
    <td>
        <?= $row['wc_product_object']->get_sku() ?>
    </td>
    <td>
        <?php
        switch ($row['wc_product_object']->get_type()) {
            case 'external':
                echo __('Externo');

                break;
            case 'grouped':
                echo __('Composto');

                break;
            case 'simple':
                echo __('Simples');

                break;
            case 'variable':
                echo __('Variável');

                break;
            case 'variation':
                echo __('Variação');

                break;
            default:
                echo __('Outro');

                break;
        }
        ?>
    </td>
    <td>
        <?= ($row['tool_alert_message'] ?? '') ?: '---' ?>
    </td>
    <td>
        <div class="dropdown">
            <button type="button" class="dropdown--manager button button-primary">
                <?= __('Mais') ?> &#8628;
            </button>
            <div class="dropdown__content">
                <ul>
                    <?php if (!empty($row['wc_product_link'])) : ?>
                        <li>
                            <a target="_blank" href="<?= $row['wc_product_link'] ?>">
                                <?= __('Ver no WooCommerce') ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($row['moloni_product_link'])) : ?>
                        <li>
                            <a target="_blank" href="<?= $row['moloni_product_link'] ?>">
                                <?= __('Ver no Moloni') ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($row['tool_show_create_button'])) : ?>
                        <li>
                            <button type="button" class="export_product">
                                <?= __('Exportar produto') ?>
                            </button>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($row['tool_show_update_stock_button'])) : ?>
                        <li>
                            <button type="button" class="export_stock">
                                <?= __('Exportar stock') ?>
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </td>
</tr>
