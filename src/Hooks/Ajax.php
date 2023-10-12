<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Exceptions\Core\MoloniException;
use Moloni\Exceptions\DocumentError;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Plugin;
use Moloni\Services\Orders\CreateMoloniDocument;
use Moloni\Services\Orders\DiscardOrder;
use Moloni\Start;
use Moloni\Storage;

class Ajax
{
    public $parent;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('wp_ajax_genInvoice', [$this, 'genInvoice']);
        add_action('wp_ajax_discardOrder', [$this, 'discardOrder']);
    }

    public function genInvoice()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new CreateMoloniDocument((int)$_REQUEST['id']);
        $orderName = $service->getOrderNumber();

        try {
            $service->run();

            $response = [
                'valid' => 1,
                'message' => sprintf(__('Documento %s inserido com sucesso'), $service->getOrderNumber())
            ];
        } catch (DocumentWarning $e) {
            Storage::$LOGGER->alert(
                str_replace('{0}', $orderName, __('Houve um alerta ao gerar o documento ({0})')),
                [
                    'tag' => 'ajax:document:create:warning',
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            $response = [
                'valid' => 0,
                'message' => $e->getMessage(),
                'description' => $e->getError()
            ];
        } catch (DocumentError $e) {
            Storage::$LOGGER->error(
                str_replace('{0}', $orderName, __('Houve um erro ao gerar o documento ({0})')),
                [
                    'tag' => 'ajax:document:create:error',
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            $response = [
                'valid' => 0,
                'message' => $e->getMessage(),
                'description' => $e->getError()
            ];
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__('Fatal error'), [
                'tag' => 'ajax:document:create:fatalerror',
                'message' => $e->getMessage()
            ]);

            $response = ['valid' => 0, 'message' => $e->getMessage()];
        }

        $this->sendJson($response);
    }

    public function discardOrder()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $response = [
            'valid' => 1
        ];

        $order = wc_get_order((int)$_REQUEST['id']);

        $service = new DiscardOrder($order);
        $service->run();
        $service->saveLog();

        $this->sendJson($response);
    }

    //             Privates             //

    private function isAuthed(): bool
    {
        return Start::login(true);
    }

    /**
     * Return and stop execution afterward.
     *
     * @see https://developer.wordpress.org/reference/hooks/wp_ajax_action/
     *
     * @param array $data
     * @return void
     */
    private function sendJson(array $data)
    {
        wp_send_json($data);
        wp_die();
    }
}
