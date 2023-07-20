<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Log;
use Moloni\Error;
use Moloni\Start;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Storage;
use Moloni\Warning;
use Moloni\Services\Mails\DocumentFailed;
use Moloni\Services\Mails\DocumentWarning;
use Moloni\Services\Orders\CreateMoloniDocument;

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
                        $service = new CreateMoloniDocument($orderId);
                        $orderName = $service->getOrderNumber();

                        Storage::$LOGGER->info(
                            str_replace('{0}', $orderName, __("A gerar automaticamente o documento da encomenda no estado 'Completed' ({0})"))
                        );

                        try {
                            $service->run();

                            $this->throwMessages($service);
                        } catch (Warning $e) {

                            $this->sendWarningEmail($orderName);

                            Notice::addmessagecustom(htmlentities($e->getError()));
                            Storage::$LOGGER->alert(
                                str_replace('{0}', $orderName, __('Houve um alerta ao gerar o documento ({0})')),
                                [
                                    'message' => $e->getMessage(),
                                    'request' => $e->getRequest()
                                ]
                            );
                        } catch (Error $e) {
                            $this->sendErrorEmail($orderName);

                            Notice::addmessagecustom(htmlentities($e->getError()));
                            Storage::$LOGGER->error(
                                str_replace('{0}', $orderName, __('Houve um erro ao gerar o documento ({0})')),
                                [
                                    'message' => $e->getMessage(),
                                    'request' => $e->getRequest()
                                ]
                            );
                        }

                        $this->removeOrderFromDocumentsInProgress($orderId);
                    }
                }
            }
        } catch (Exception $ex) {
            Storage::$LOGGER->critical(__('Erro fatal'), [
                'action' => 'automatic:document:create:complete',
                'exception' => $ex->getMessage()
            ]);
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
                $service = new CreateMoloniDocument($orderId);
                $orderName = $service->getOrderNumber();

                Storage::$LOGGER->info(
                    str_replace('{0}', $orderName, __("A gerar automaticamente o documento da encomenda no estado 'Processing' ({0})"))
                );

                try {
                    $service->run();

                    $this->throwMessages($service);
                } catch (Warning $e) {
                    $this->sendWarningEmail($service->getOrderNumber());

                    Notice::addmessagecustom(htmlentities($e->getError()));
                    Storage::$LOGGER->alert(
                        str_replace('{0}', $orderName, __('Houve um alerta ao gerar o documento ({0})')),
                        [
                            'message' => $e->getMessage(),
                            'request' => $e->getRequest()
                        ]
                    );
                } catch (Error $e) {
                    $this->sendErrorEmail($service->getOrderNumber());

                    Notice::addmessagecustom(htmlentities($e->getError()));
                    Storage::$LOGGER->error(
                        str_replace('{0}', $orderName, __('Houve um erro ao gerar o documento ({0})')),
                        [
                            'message' => $e->getMessage(),
                            'request' => $e->getRequest()
                        ]
                    );
                }

                $this->removeOrderFromDocumentsInProgress($orderId);
            }
        } catch (Exception $ex) {
            Storage::$LOGGER->critical(__('Erro fatal'), [
                'action' => 'automatic:document:create:processing',
                'exception' => $ex->getMessage()
            ]);
        }
    }

    private function sendWarningEmail($orderName): void
    {
        if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
            new DocumentWarning(ALERT_EMAIL, $orderName);
        }
    }

    private function sendErrorEmail($orderName): void
    {
        if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
            new DocumentFailed(ALERT_EMAIL, $orderName);
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
