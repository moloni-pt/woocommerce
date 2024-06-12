<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Exceptions\Core\MoloniException;
use Moloni\Exceptions\DocumentWarning as DocumentWarningException;
use Moloni\Start;
use Moloni\Notice;
use Moloni\Plugin;
use Moloni\Storage;
use Moloni\Services\Mails\DocumentFailed;
use Moloni\Services\Mails\DocumentWarning;
use Moloni\Services\Orders\CreateMoloniDocument;

class OrderStatusChanged
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

        add_action('woocommerce_order_status_changed', [$this, 'orderStatusChanged'], 10, 3);
    }

    public function orderStatusChanged($orderId, $previousStatus, $nextStatus)
    {
        try {
            /** Check if login is valid */
            if (!Start::login(true)) {
                return;
            }

            /** Check if automatic document creation is active */
            if (!defined('INVOICE_AUTO') || empty(INVOICE_AUTO)) {
                return;
            }

            /** Check if a status was chosen */
            if (!defined('INVOICE_AUTO_STATUS')) {
                return;
            }

            $validStatus = [INVOICE_AUTO_STATUS];
            $validStatus = apply_filters('moloni_before_order_status_changed', $validStatus, $orderId, $previousStatus, $nextStatus);

            /** Check if next status was the chosen one */
            if (!in_array($nextStatus, $validStatus, true)) {
                return;
            }

            /** Check if order is already being processed */
            if (!$this->addOrderToDocumentsInProgress($orderId)) {
                return;
            }

            $service = new CreateMoloniDocument($orderId);
            $orderName = $service->getOrderNumber();

            Storage::$LOGGER->info(
                str_replace(['{0}', '{1}'], [$nextStatus, $orderName], __("A gerar automaticamente o documento da encomenda no estado '{0}' ({1})"))
            );

            try {
                $service->run();

                $this->throwMessages($service);
            } catch (DocumentWarningException $e) {

                $this->sendWarningEmail($orderName);

                Notice::addmessagecustom(htmlentities($e->getError()));
                Storage::$LOGGER->alert(
                    str_replace('{0}', $orderName, __('Houve um alerta ao gerar o documento ({0})')),
                    [
                        'message' => $e->getMessage(),
                        'request' => $e->getData()
                    ]
                );
            } catch (MoloniException $e) {
                $this->sendErrorEmail($orderName);

                Notice::addmessagecustom(htmlentities($e->getError()));
                Storage::$LOGGER->error(
                    str_replace('{0}', $orderName, __('Houve um erro ao gerar o documento ({0})')),
                    [
                        'message' => $e->getMessage(),
                        'request' => $e->getData()
                    ]
                );
            }

            $this->removeOrderFromDocumentsInProgress($orderId);
        } catch (Exception $ex) {
            Storage::$LOGGER->critical(__('Erro fatal'), [
                'action' => "automatic:document:create:$nextStatus",
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
