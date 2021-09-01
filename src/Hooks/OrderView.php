<?php

namespace Moloni\Hooks;

use Moloni\Error;
use Moloni\Plugin;
use Moloni\Start;

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
        add_meta_box('moloni_add_meta_box', 'Moloni', [$this, 'showMoloniView'], 'shop_order', 'side', 'core');
    }

    public function getDocumentTypeSelect()
    {
        ?>

        <select id="moloni_document_type" style="float:right">
            <option value='invoices' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoices' ? 'selected' : '') ?>>
                <?= __('Fatura') ?>
            </option>

            <option value='invoiceReceipts' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoiceReceipts' ? 'selected' : '') ?>>
                <?= __('Factura/Recibo') ?>
            </option>

            <option value='simplifiedInvoices'<?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'simplifiedInvoices' ? 'selected' : '') ?>>
                <?= __('Factura Simplificada') ?>
            </option>

            <option value='proFormaInvoices' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'proFormaInvoices' ? 'selected' : '') ?>>
                <?= __('Fatura Pró-Forma') ?>
            </option>

            <option value='billsOfLading' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'billsOfLading' ? 'selected' : '') ?>>
                <?= __('Guia de Transporte') ?>
            </option>

            <option value='purchaseOrder' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'purchaseOrder' ? 'selected' : '') ?>>
                <?= __('Nota de Encomenda') ?>
            </option>

            <option value='estimates' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'estimates' ? 'selected' : '') ?>>
                <?= __('Orçamento') ?>
            </option>
        </select>

        <?php
    }

    public function seeDocument($documentId)
    {
        ?>
        <a type="button"
           class="button button-primary"
           target="_BLANK"
           href="<?= admin_url('admin.php?page=moloni&action=getInvoice&id=' . $documentId) ?>"
           style="margin-top: 10px; float:right;"
        >
            <?= __('Ver documento') ?>
        </a>
        <div style="clear:both"></div>

        <?php
    }

    public function reCreateDocument($post)
    {
        ?>
        <a type="button"
           class="button"
           target="_BLANK"
           href="<?= admin_url('admin.php?page=moloni&action=genInvoice&id=' . $post->ID) ?>"
           style="margin-top: 10px; float:right;"
        >
            <?= __('Gerar novamente') ?>
        </a>
        <?php
    }

    public function showMoloniView($post)
    {
        if (in_array($post->post_status, $this->allowedStatus)) : ?>
            <?php $documentId = get_post_meta($post->ID, '_moloni_sent', true); ?>

            <?php
            $order = new \WC_Order($post->ID);
            echo '<div style="display: none"><pre>' . print_r($order->get_taxes(), true) . '</pre></div>';
            ?>

            <?php if ((int)$documentId > 0) : ?>
                <?= __('O documento já foi gerado no moloni') ?>
                <?php $this->seeDocument($documentId) ?>
                <?php $this->reCreateDocument($post) ?>
            <?php elseif ($documentId == -1) : ?>
                <?= __('O documento foi marcado como gerado.') ?>
                <?php try {
                    Start::login(true);
                } catch (Error $e) {
                } ?>
                <br><br>
                <?php $this->getDocumentTypeSelect() ?>
                <br><br>
                <?php $this->getDocumentCreateButton($post, __('Gerar novamente')) ?>
            <?php else: ?>
                <?php try {
                    Start::login(true);
                } catch (Error $e) {
                } ?>

                <?php $this->getDocumentCreateButton($post, __('Gerar')) ?>
                <?php $this->getDocumentTypeSelect() ?>
            <?php endif; ?>
            <div style="clear:both"></div>
        <?php else : ?>
            <?= __('A encomenda tem que ser dada como paga para poder ser gerada.') ?>
        <?php endif;
    }

    public function getDocumentCreateButton($post, $text = 'Gerar')
    {
        ?>
        <a type="button"
           class="button-primary"
           target="_BLANK"
           onclick="createMoloniDocument()"
           style="margin-left: 5px; float:right;"
        >
            <?= $text ?>
        </a>

        <script>
            function createMoloniDocument() {
                var redirectUrl = "<?= admin_url('admin.php?page=moloni&action=genInvoice&id=' . $post->ID) ?>";
                if (document.getElementById('moloni_document_type')) {
                    redirectUrl += '&document_type=' + document.getElementById('moloni_document_type').value;
                }
                window.open(redirectUrl, '_blank')
            }
        </script>
        <?php
    }
}
