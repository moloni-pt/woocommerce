<?php

namespace Moloni;

use Moloni\Controllers\Documents;
use Moloni\Services\Orders\CreateMoloniDocument;

class Ajax
{
    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        add_action('wp_ajax_genInvoice', [$this, 'genInvoice']);
    }

    public function genInvoice(): void
    {
        $service = new CreateMoloniDocument((int)$_REQUEST['id']);
        $orderName = $service->getOrderNumber();

        try {
            if (Start::login(true)) {
                $service->run();

                wp_send_json([
                    'valid' => 1,
                    'message' => sprintf(__('Documento %s inserido com sucesso'), $service->getOrderNumber())
                ]);
            }
        } catch (Warning $e) {
            Storage::$LOGGER->alert(
                str_replace('{0}', $orderName, __('Houve um alerta ao gerar o documento ({0})')),
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getRequest()
                ]
            );

            wp_send_json([
                'valid' => 0,
                'message' => $e->getMessage(),
                'description' => $e->getError()
            ]);
        } catch (Error $e) {
            Storage::$LOGGER->error(
                str_replace('{0}', $orderName, __('Houve um erro ao gerar o documento ({0})')),
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getRequest()
                ]
            );

            wp_send_json([
                'valid' => 0,
                'message' => $e->getMessage(),
                'description' => $e->getError()
            ]);
        }
    }
}
