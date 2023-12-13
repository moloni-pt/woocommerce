<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Controllers\Product;
use Moloni\Curl;
use Moloni\Exceptions\Core\MoloniException;
use Moloni\Exceptions\DocumentError;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Exceptions\GenericException;
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

        add_action('wp_ajax_toolsCreateWcProduct', [$this, 'toolsCreateWcProduct']);
        add_action('wp_ajax_toolsUpdateWcStock', [$this, 'toolsUpdateWcStock']);
        add_action('wp_ajax_toolsCreateMoloniProduct', [$this, 'toolsCreateMoloniProduct']);
        add_action('wp_ajax_toolsUpdateMoloniStock', [$this, 'toolsUpdateMoloniStock']);
    }

    //             Public's             //

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


    public function toolsCreateWcProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'action' => 'toolsCreateWcProduct'
            ]
        ];

        try {
            $mlProduct = Curl::simple('products/getOne', ['product_id' => $mlProductId]);

            if (empty($mlProduct)) {
                throw new GenericException('Produto Moloni não encontrado');
            }

            $wcProductId = wc_get_product_id_by_sku($mlProduct['reference']);

            if (!empty($wcProductId)) {
                throw new GenericException('Produto WooCommerce já existe');
            }

            $service = new \Moloni\Services\WcProducts\CreateProduct($mlProduct);
            $service->run();
            $service->saveLog();

            $response['product_row'] = ''; // todo: this
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function toolsUpdateWcStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'wc_product_id' => $wcProductId,
                'action' => 'toolsUpdateWcStock'
            ]
        ];

        try {
            $mlProduct = Curl::simple('products/getOne', ['product_id' => $mlProductId]);

            if (empty($mlProduct)) {
                throw new GenericException('Produto Moloni não encontrado');
            }

            if (empty($mlProduct['has_stock'])) {
                throw new GenericException('Produto Moloni não movimenta stock');
            }

            $wcProduct = wc_get_product($wcProductId);

            if (empty($wcProduct)) {
                throw new GenericException('Produto WooCommerce não encontrado');
            }

            if ($mlProduct['reference'] !== $wcProduct->get_sku()) {
                throw new GenericException('Produtos não coincidem');
            }

            $service = new \Moloni\Services\WcProducts\UpdateProductStock($wcProduct, $mlProduct);
            $service->run();
            $service->saveLog();

            $response['product_row'] = ''; // todo: this
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function toolsCreateMoloniProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'wc_product_id' => $wcProductId,
                'action' => 'toolsCreateMoloniProduct'
            ]
        ];

        try {
            $wcProduct = wc_get_product($wcProductId);

            if (empty($wcProduct)) {
                throw new GenericException('Produto WooCommerce não encontrado');
            }

            if (empty($wcProduct->get_sku())) {
                throw new GenericException('Produto WooCommerce não tem referência');
            }

            $service = new Product($wcProduct);

            if ($service->loadByReference()) {
                throw new GenericException('Produto Moloni já existe');
            }

            $service->create();

            $response['product_row'] = ''; // todo: this
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function toolsUpdateMoloniStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'wc_product_id' => $wcProductId,
                'action' => 'toolsUpdateMoloniStock'
            ]
        ];

        try {
            $wcProduct = wc_get_product($wcProductId);

            if (empty($wcProduct)) {
                throw new GenericException('Produto WooCommerce não encontrado');
            }

            $mlProduct = Curl::simple('products/getOne', ['product_id' => $mlProductId]);

            if (empty($mlProduct)) {
                throw new GenericException('Produto Moloni não encontrado');
            }

            if ($mlProduct['reference'] !== $wcProduct->get_sku()) {
                throw new GenericException('Produtos não coincidem');
            }

            $service = new \Moloni\Services\MoloniProducts\UpdateProductStock($mlProduct, $wcProduct);
            $service->run();
            $service->saveLog();

            $response['product_row'] = ''; // todo: this
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

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
