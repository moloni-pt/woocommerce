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
        <?= $row['moloni_product_array']['name'] ?? '---' ?>
    </td>
    <td>
        <?= $row['moloni_product_array']['reference'] ?? '---' ?>
    </td>
    <td>
        <?php
        switch ((int)$row['moloni_product_array']['composition_type']) {
            case CompositionTypes::BUNDLE:
                echo __('Composto');

                break;
            case CompositionTypes::MANUFACTURED_COMPOSITION:
                echo __('Fabricado de composição');

                break;
            case CompositionTypes::MANUFACTURED_DECOMPOSITION:
                echo __('Fabricado de decomposição');

                break;
            case CompositionTypes::SIMPLE:
            default:
                echo __('Simples');

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
                    <li>
                        <a target="_blank" href="<?= $row['moloni_product_link'] ?>">
                            <?= __('Ver no Moloni') ?>
                        </a>
                    </li>

                    <?php if (!empty($row['wc_product_link'])) : ?>
                        <li>
                            <a target="_blank" href="<?= $row['wc_product_link'] ?>">
                                <?= __('Ver no WooCommerce') ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($row['tool_show_create_button'])) : ?>
                        <li>
                            <button type="button" class="import_product">
                                <?= __('Importar produto') ?>
                            </button>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($row['tool_show_update_stock_button'])) : ?>
                        <li>
                            <button type="button" class="import_stock">
                                <?= __('Importar stock') ?>
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </td>
</tr>
