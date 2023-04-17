<?php

namespace Moloni;

use WC_Order;
use Moloni\Helpers\Logger;
use Moloni\Helpers\Context;
use Moloni\Controllers\Logs;
use Moloni\Services\Documents\OpenDocument;
use Moloni\Services\Documents\DownloadDocument;
use Moloni\Services\Orders\CreateMoloniDocument;

/**
 * Main constructor
 * Class Plugin
 * @package Moloni
 */
class Plugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->onStart();
        $this->actions();
        $this->crons();
    }

    //            Privates            //

    /**
     * Place to run code before starting
     *
     * @return void
     */
    private function onStart()
    {
        Storage::$USES_NEW_ORDERS_SYSTEM = Context::isNewOrdersSystemEnabled();
        Storage::$LOGGER = new Logger();
    }

    /**
     * Start actions
     *
     * @return void
     */
    private function actions(): void
    {
        new Menus\Admin($this);
        new Hooks\WoocommerceInitialize($this);
        new Hooks\ProductUpdate($this);
        new Hooks\ProductView($this);
        new Hooks\OrderView($this);
        new Hooks\OrderPaid($this);
        new Hooks\OrderList($this);
        new Hooks\UpgradeProcess($this);
        new Ajax($this);
    }

    /**
     * Setting up the crons if needed
     */
    private function crons(): void
    {
        add_filter('cron_schedules', '\Moloni\Crons::addCronInterval');
        add_action('moloniProductsSync', '\Moloni\Crons::productsSync');

        if (!wp_next_scheduled('moloniProductsSync')) {
            wp_schedule_event(time(), 'everyficeminutes', 'moloniProductsSync');
        }
    }

    //            Publics            //

    /**
     * Main function
     * This will run when accessing the page "moloni" and the routing shoud be done here with and $_GET['action']
     */
    public function run(): void
    {
        try {
            $authenticated = Start::login();

            /** If the user is not logged in show the login form */
            if ($authenticated) {
                $action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';

                switch ($action) {
                    case 'remInvoice':
                        $this->removeOrder();
                        break;
                    case 'remInvoiceAll':
                        $this->removeOrdersAll();
                        break;
                    case 'genInvoice':
                        $this->createDocument();
                        break;
                    case 'syncStocks':
                        $this->syncStocks();
                        break;
                    case 'remLogs':
                        $this->removeLogs();
                        break;
                    case 'getInvoice':
                        $this->openDocument();
                        break;
                    case 'downloadDocument':
                        $this->downloadDocument();
                        break;
                }
            }
        } catch (Error $error) {
            $pluginErrorException = $error;
        }

        if (isset($authenticated) && $authenticated && !wp_doing_ajax()) {
            include MOLONI_TEMPLATE_DIR . 'MainContainer.php';
        }
    }

    //            Actions            //

    /**
     * Create a single document from order
     *
     * @throws Warning|Error
     */
    private function createDocument(): void
    {
        $service = new CreateMoloniDocument((int)$_REQUEST['id']);
        $orderName = $service->getOrderNumber();

        try {
            $service->run();
        } catch (Warning $e) {
            Storage::$LOGGER->alert(
                str_replace('{0}', $orderName, __('Houve um alerta ao gerar o documento ({0})')),
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getRequest()
                ]
            );

            throw $e;
        } catch (Error $e) {
            Storage::$LOGGER->critical(
                str_replace('{0}', $orderName, __('Houve um erro ao gerar o documento ({0})')),
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getRequest()
                ]
            );

            throw $e;
        }

        if ($service->getDocumentId()) {
            $adminUrl = admin_url('admin.php?page=moloni&action=getInvoice&id=' . $service->getDocumentId());

            $html = ' <a href="' . $adminUrl . '" target="_BLANK">';
            $html .= '  Ver documento';
            $html .= '</a>';

            add_settings_error('moloni', 'moloni-document-created-success', __('O documento foi gerado!') . $html, 'updated');
        }
    }

    /**
     * Open Moloni document
     *
     * @return void
     */
    private function openDocument(): void
    {
        $documentId = (int)$_REQUEST['id'];

        if ($documentId > 0) {
            new OpenDocument($documentId);
        }

        add_settings_error('moloni', 'moloni-document-not-found', __('Documento não encontrado'));
    }

    /**
     * Download Moloni document
     *
     * @return void
     */
    private function downloadDocument(): void
    {
        $documentId = (int)$_REQUEST['id'];

        if ($documentId > 0) {
            new DownloadDocument($documentId);
        }
    }

    /**
     * Delete logs
     *
     * @return void
     */
    private function removeLogs(): void
    {
        Logs::removeOlderLogs();

        add_settings_error('moloni', 'moloni-rem-logs', __('A limpeza de logs foi concluída.'), 'updated');
    }

    /**
     * Discard single order from pending list
     *
     * @return void
     */
    private function removeOrder(): void
    {
        $orderId = (int)$_GET['id'];

        if (isset($_GET['confirm']) && sanitize_text_field($_GET['confirm']) === 'true') {
            $order = wc_get_order($orderId);
            $order->add_meta_data('_moloni_sent', '-1');
            $order->add_order_note(__('Encomenda marcada como gerada'));
            $order->save();

            $msg = str_replace('{0}', $order->get_order_number(), __('A encomenda foi marcada como gerada ({0})'));

            Storage::$LOGGER->info($msg);
            add_settings_error('moloni', 'moloni-order-remove-success', $msg, 'updated');
        } else {
            add_settings_error(
                'moloni',
                'moloni-order-remove',
                __('Confirma que pretende marcar a encomenda ' . $orderId . " como paga? <a href='" . admin_url('admin.php?page=moloni&action=remInvoice&confirm=true&id=' . $orderId) . "'>Sim confirmo!</a>")
            );
        }
    }

    /**
     * Discard all orders from pending list
     *
     * @return void
     */
    private function removeOrdersAll(): void
    {
        if (isset($_GET['confirm']) && sanitize_text_field($_GET['confirm']) === 'true') {
            /** @var WC_Order[] $allOrders */
            $allOrders = Controllers\PendingOrders::getAllAvailable();

            if (!empty($allOrders)) {
                foreach ($allOrders as $order) {
                    $order->add_meta_data('_moloni_sent', '-1');
                    $order->add_order_note(__('Encomenda marcada como gerada'));
                    $order->save();
                }

                $msg = __('Todas as encomendas foram marcadas como geradas!');

                Storage::$LOGGER->info($msg);
                add_settings_error('moloni', 'moloni-order-all-remove-success', $msg, 'updated');
            } else {
                add_settings_error('moloni', 'moloni-order-all-remove-not-found', __('Não foram encontradas encomendas por gerar'));
            }
        } else {
            add_settings_error(
                'moloni', 'moloni-order-remove', __("Confirma que pretende marcar todas as encomendas como já geradas? <a href='" . admin_url('admin.php?page=moloni&action=remInvoiceAll&confirm=true') . "'>Sim confirmo!</a>")
            );
        }
    }

    /**
     * Force stock syncronization
     *
     * @return void
     */
    private function syncStocks(): void
    {
        $date = isset($_GET['since']) ? sanitize_text_field($_GET['since']) : gmdate('Y-m-d', strtotime('-1 week'));

        $service = new Controllers\SyncProducts($date);
        $service->run();

        if ($service->countUpdated() > 0) {
            add_settings_error('moloni', 'moloni-sync-stocks-updated', __('Foram actualizados ' . $service->countUpdated() . ' artigos.'), 'updated');
        }

        if ($service->countEqual() > 0) {
            add_settings_error('moloni', 'moloni-sync-stocks-updated', __('Existem ' . $service->countEqual() . ' artigos com stock igual.'), 'updated');
        }

        if ($service->countNotFound() > 0) {
            add_settings_error('moloni', 'moloni-sync-stocks-not-found', __('Não foram encontrados no WooCommerce ' . $service->countNotFound() . ' artigos.'));
        }

        if ($service->countFoundRecord() > 0) {
            Storage::$LOGGER->info(__('Sincronização de stock manual'), [
                'since' => $service->getSince(),
                'equal' => $service->getEqual(),
                'not_found' => $service->getNotFound(),
                'get_updated' => $service->getUpdated(),
            ]);
        }
    }
}
