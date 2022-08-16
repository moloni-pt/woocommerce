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
        try {
            if (Start::login(true)) {
                $service = new CreateMoloniDocument((int)$_REQUEST['id']);
                $service->run();

                wp_send_json([
                    'valid' => 1,
                    'message' => sprintf(__('Documento %s inserido com sucesso'), $service->getOrderNumber())
                ]);
            }
        } catch (Error $e) {
            wp_send_json([
                'valid' => 0,
                'message' => $e->getMessage(),
                'description' => $e->getError()
            ]);
        }
    }
}
