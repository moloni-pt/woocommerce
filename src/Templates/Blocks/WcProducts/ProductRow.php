<?php

if (!defined('ABSPATH')) {
    exit;
}

$row = $row ?? [];
?>

<tr class="product__row"
    data-wc-id="<?= esc_html($row['wc_product_id'] ?? 0) ?>"
    data-moloni-id="<?= esc_html($row['moloni_product_id'] ?? 0) ?>"
>
    <td class="product__row-name">
        <?= esc_html($row['wc_product_object']->get_name()); ?>
    </td>
    <td class="product__row-reference">
        <?= esc_html($row['wc_product_object']->get_sku()); ?>
    </td>
    <td>
        <?php
        switch ($row['wc_product_object']->get_type()) {
            case 'external':
                esc_html_e('Externo');

                break;
            case 'grouped':
                esc_html_e('Composto');

                break;
            case 'simple':
                esc_html_e('Simples');

                break;
            case 'variable':
                esc_html_e('Variável');

                break;
            case 'variation':
                esc_html_e('Variação');

                break;
            default:
                esc_html_e('Outro');

                break;
        }
        ?>
    </td>
    <td>
        <?= esc_html(($row['tool_alert_message'] ?? '') ?: '---') ?>
    </td>
    <td>
        <?php if (!empty($row['wc_product_link']) || !empty($row['moloni_product_link'])) : ?>
            <div class="dropdown">
                <button type="button" class="dropdown--manager button button-primary">
                    <?php esc_html_e('Ver') ?> &#8628;
                </button>
                <div class="dropdown__content">
                    <ul>
                        <?php if (!empty($row['wc_product_link'])) : ?>
                            <li>
                                <a target="_blank" href="<?= esc_url($row['wc_product_link']) ?>">
                                    <?php esc_html_e('Ver no WooCommerce') ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($row['moloni_product_link'])) : ?>
                            <li>
                                <a target="_blank" href="<?= esc_url($row['moloni_product_link']) ?>">
                                    <?php esc_html_e('Ver no Moloni') ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <input type="checkbox" class="checkbox_create_product m-0-important"
            <?= empty($row['tool_show_create_button']) ? 'disabled' : '' ?>
        >
    </td>
    <td class="text-center">
        <input type="checkbox" class="checkbox_update_stock_product m-0-important"
            <?= empty($row['tool_show_update_stock_button']) ? 'disabled' : '' ?>
        >
    </td>
</tr>
