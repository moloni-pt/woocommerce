<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Error;
use Moloni\Log;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Services\Orders\CreateMoloniDocument;
use Moloni\Start;
use Moloni\Controllers\Documents;

class OrderPaid
{
    public $parent;

    /**
     * Constructor
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_order_status_completed', [$this, 'documentCreateComplete']);
        add_action('woocommerce_order_status_processing', [$this, 'documentCreateProcessing']);
    }

    public function documentCreateComplete($orderId): void
    {
        try {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (Start::login(true) && defined('INVOICE_AUTO') && INVOICE_AUTO) {
                if (!defined('INVOICE_AUTO_STATUS') || (defined('INVOICE_AUTO_STATUS') && INVOICE_AUTO_STATUS === 'completed')) {
                    if ($this->addOrderToDocumentsInProgress($orderId)) {
                        Log::setFileName('DocumentsAuto');
                        Log::write('A gerar automaticamente o documento da encomenda no estado "Completed" ' . $orderId);

                        try {
                            $service = new CreateMoloniDocument($orderId);
                            $service->run();

                            $this->throwMessages($service);
                        } catch (Error $e) {
                            Notice::addmessagecustom(htmlentities($e->getError()));
                            Log::write('Houve um erro ao gerar o documento: ' . strip_tags($e->getDecodedMessage()));
                        }

                        $this->removeOrderFromDocumentsInProgress($orderId);
                    }
                }
            }
        } catch (Exception $ex) {
            Log::write('Fatal error: ' . $ex->getMessage());
        }
    }

    public function documentCreateProcessing($orderId): void
    {
        try {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (Start::login(true)
                && defined('INVOICE_AUTO')
                && INVOICE_AUTO && defined('INVOICE_AUTO_STATUS')
                && INVOICE_AUTO_STATUS === 'processing'
                && $this->addOrderToDocumentsInProgress($orderId)) {
                Log::setFileName('DocumentsAuto');
                Log::write('A gerar automaticamente o documento da encomenda no estado "Processing" ' . $orderId);

                try {
                    $service = new CreateMoloniDocument($orderId);
                    $service->run();

                    $this->throwMessages($service);
                } catch (Error $e) {
                    Notice::addmessagecustom(htmlentities($e->getError()));
                    Log::write('Houve um erro ao gerar o documento: ' . strip_tags($e->getDecodedMessage()));
                }

                $this->removeOrderFromDocumentsInProgress($orderId);
            }
        } catch (Exception $ex) {
            Log::write('Fatal error: ' . $ex->getMessage());
        }
    }

    private function addOrderToDocumentsInProgress($orderId): bool
    {
        $moloniDocuments = get_option('_moloni_documents_in_progress');

        if ($moloniDocuments !== false && isset($moloniDocuments[$orderId])) {
            return false;
        }

        if ($moloniDocuments === false) {
            add_option('_moloni_documents_in_progress', [$orderId => true]);
        } else {
            $moloniDocuments[$orderId] = true;
            update_option('_moloni_documents_in_progress', $moloniDocuments);
        }

        return true;
    }

    private function removeOrderFromDocumentsInProgress($orderId): void
    {
        $moloniDocuments = get_option('_moloni_documents_in_progress');

        if (is_array($moloniDocuments)) {
            unset($moloniDocuments[$orderId]);

            update_option('_moloni_documents_in_progress', $moloniDocuments);
        }
    }

    private function throwMessages(CreateMoloniDocument $service): void
    {
        if ($service->getDocumentId() && is_admin()) {
            $adminUrl = admin_url('admin.php?page=moloni&action=getInvoice&id=' . $service->getDocumentId());
            $html = ' <a href="' . $adminUrl . '" target="_BLANK">Ver documento</a>';

            add_settings_error('moloni', 'moloni-document-created-success', __('O documento foi gerado!') . $html, 'updated');
        }
    }
}
