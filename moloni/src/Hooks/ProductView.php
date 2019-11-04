<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Controllers\Product;
use Moloni\Error;
use Moloni\Plugin;
use Moloni\Start;
use WC_Product;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the product view
 * @package Moloni\Hooks
 */
class ProductView
{

    public $parent;

    /** @var WC_Product */
    public $product;

    /** @var Product */
    public $moloniProduct;


    private $allowedPostTypes = ["product"];

    /**
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box($post_type)
    {
        if (in_array($post_type, $this->allowedPostTypes)) {
            add_meta_box('woocommerce_product_options_general_product_data', 'Moloni', [$this, 'showMoloniView'], null, 'side');
        }
    }

    /**
     * @return null|void
     */
    public function showMoloniView()
    {
        try {
            if (Start::login()) {
                $this->product = wc_get_product(get_the_ID());

                if (!$this->product) {
                    return null;
                }

                $this->moloniProduct = new Product($this->product);

                try {
                    if (!$this->moloniProduct->loadByReference()) {
                        echo sprintf(__("Artigo com a referência %s não encontrado"), $this->moloniProduct->reference);
                        return null;
                    }

                    $this->showProductDetails();
                } catch (Error $e) {
                    echo __("Erro ao obter artigo");
                    return null;
                }
            } else {
                echo __("Login Moloni inválido");
            }
        } catch (Exception $exception) {

        }
    }

    private function showProductDetails()
    {
        ?>
        <div>
            <p>
                <b><?= __("Referência: ") ?></b> <?= $this->moloniProduct->reference ?><br>
                <b><?= __("Preço: ") ?></b> <?= $this->moloniProduct->price ?>€<br>
                <?php if ($this->moloniProduct->has_stock == 1) : ?>
                    <b><?= __("Stock: ") ?></b> <?= $this->moloniProduct->stock ?>
                <?php endif; ?>

                <?php if (defined("COMPANY_SLUG")) : ?>
                    <a type="button"
                       class="button button-primary"
                       target="_BLANK"
                       href="https://moloni.pt/<?= COMPANY_SLUG ?>/Artigos/showUpdate/<?= $this->moloniProduct->product_id ?>/<?= $this->moloniProduct->category_id ?>"
                       style="margin-top: 10px; float:right;"
                    > Ver Artigo </a>
                <?php endif; ?>

                <?php

                echo "<pre style='display: none'>";
                print_r($this->product->get_meta_data());
                print_r($this->product->get_default_attributes());
                print_r($this->product->get_attributes());
                print_r($this->product->get_data());
                echo "</pre>";

                ?>
            </p>
        </div>
        <?php
    }

}
