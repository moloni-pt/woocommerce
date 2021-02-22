<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Log;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Start;
use Moloni\Controllers\Documents;

class OrderPaid
{

    public $parent;

    /**
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_order_status_completed', [$this, 'documentCreateComplete']);
        add_action('woocommerce_order_status_processing', [$this, 'documentCreateProcessing']);
    }

    public function documentCreateComplete($orderId)
    {
        try {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (Start::login(true) && defined('INVOICE_AUTO') && INVOICE_AUTO) {
                if (!defined('INVOICE_AUTO_STATUS') || (defined('INVOICE_AUTO_STATUS') && INVOICE_AUTO_STATUS === 'completed')) {
                    Log::setFileName('DocumentsAuto');
                    Log::write('A gerar automaticamente o documento da encomenda no estado "Completed" ' . $orderId);

                    $document = new Documents($orderId);
                    $document->createDocument();

                    $this->throwMessages($document);
                }
            }
        } catch (Exception $ex) {
            Log::write('Fatal error: ' . $ex->getMessage());
        }
    }

    public function documentCreateProcessing($orderId)
    {
        try {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (Start::login() && defined('INVOICE_AUTO') && INVOICE_AUTO) {
                if (defined('INVOICE_AUTO_STATUS') && INVOICE_AUTO_STATUS === 'processing') {
                    Log::setFileName('DocumentsAuto');
                    Log::write('A gerar automaticamente o documento da encomenda no estado "Processing" ' . $orderId);

                    $document = new Documents($orderId);
                    $document->createDocument();

                    $this->throwMessages($document);
                }
            }
        } catch (Exception $ex) {
            Log::write('Fatal error: ' . $ex->getMessage());
        }
    }

    private function throwMessages(Documents $document)
    {
        if ($document->getError()) {
            Notice::addmessagecustom(htmlentities($document->getError()->getError()));
            Log::write('Houve um erro ao gerar o documento: ' . strip_tags($document->getError()->getDecodedMessage()));
        }

        if ($document->document_id && is_admin()) {
            $viewUrl = ' <a href="' . admin_url('admin.php?page=moloni&action=getInvoice&id=' . $document->document_id) . '" target="_BLANK">Ver documento</a>';
            add_settings_error('moloni', 'moloni-document-created-success', __('O documento foi gerado!') . $viewUrl, 'updated');
        }
    }
}
