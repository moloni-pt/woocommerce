<?php

namespace Moloni\Hooks;

use WP_Post;
use WC_Order;
use Exception;
use Moloni\Plugin;
use Moloni\Start;
use Moloni\Helpers\MoloniOrder;
use Moloni\Enums\DocumentTypes;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the order view
 * There they can create a document for that order or check the document if it was already created
 * @package Moloni\Hooks
 */
class OrderView
{

    public $parent;

    /** @var array */
    private $allowedStatus = ['wc-processing', 'wc-completed', 'wc-pending', 'wc-on-hold'];

    /**
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box()
    {
        $screen = 'shop_order';

        try {
            if (class_exists(CustomOrdersTableController::class) && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()) {
                $screen = wc_get_page_screen_id('shop-order');
            }
        } catch (Exception $ex) {}

        add_meta_box('moloni_add_meta_box', 'Moloni', [$this, 'showMoloniView'], $screen, 'side', 'core');
    }

    public function getDocumentTypeSelect()
    {
        $documentType = defined('DOCUMENT_TYPE') ? DOCUMENT_TYPE : '';
        ?>

        <select id="moloni_document_type" style="float:right">
            <?php foreach (DocumentTypes::getDocumentTypeForRender() as $id => $name) : ?>
                <option value='<?= $id ?>' <?= ($documentType === $id ? 'selected' : '') ?>>
                    <?php esc_html_e($name) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php
    }

    public function seeDocument($documentId)
    {
        ?>
        <a type="button"
           class="button button-primary"
           target="_BLANK"
           href="<?= esc_url(admin_url('admin.php?page=moloni&action=getInvoice&id=' . $documentId)) ?>"
           style="margin-top: 10px; margin-left: 10px; float:right;"
        >
            <?php esc_html_e('Ver documento') ?>
        </a>

        <?php
    }

    public function showMoloniView($postOrOrderObject)
    {
        /** @var WC_Order $order */
        $order = ($postOrOrderObject instanceof WP_Post) ? wc_get_order($postOrOrderObject->ID) : $postOrOrderObject;

        if (in_array('wc-' . $order->get_status(), $this->allowedStatus)) {
            $documentId = MoloniOrder::getLastCreatedDocument($order);

            Start::login(true);

            echo '<div style="display: none"><pre>' . print_r($order->get_taxes(), true) . '</pre></div>';

            if ($documentId > 0) {
                echo esc_html__('O documento já foi gerado no moloni');

                $this->seeDocument($documentId);
                $this->reCreateDocument($order);
            } elseif ($documentId === -1) {
                echo esc_html__('O documento foi marcado como gerado.');
                echo '<br><br>';

                $this->getDocumentTypeSelect();
                echo '<br><br>';

                $this->getDocumentCreateButton($order, __('Gerar novamente'));
            } else {
                $this->getDocumentCreateButton($order, __('Gerar'));
                $this->getDocumentTypeSelect();
            }

            echo '<div style="clear:both"></div>';
        } else {
            echo esc_html__('A encomenda tem que ser dada como paga para poder ser gerada.');
        }
    }

    public function reCreateDocument(WC_Order $order)
    {
        ?>
        <a type="button"
           class="button"
           target="_BLANK"
           href="<?= esc_url(admin_url('admin.php?page=moloni&action=genInvoice&id=' . $order->get_id())) ?>"
           style="margin-top: 10px; float:right;"
        >
            <?php esc_html_e('Gerar novamente') ?>
        </a>
        <?php
    }

    public function getDocumentCreateButton(WC_Order $order, string $text = 'Gerar')
    {
        ?>
        <a type="button"
           class="button-primary"
           target="_BLANK"
           onclick="createMoloniDocument()"
           style="margin-left: 5px; float:right;"
        >
            <?= esc_html($text) ?>
        </a>

        <script>
            function createMoloniDocument() {
                var redirectUrl = "<?= esc_url(admin_url('admin.php?page=moloni&action=genInvoice&id=' . $order->get_id())) ?>";

                if (document.getElementById('moloni_document_type')) {
                    redirectUrl += '&document_type=' + document.getElementById('moloni_document_type').value;
                }

                window.open(redirectUrl, '_blank')
            }
        </script>
        <?php
    }
}
