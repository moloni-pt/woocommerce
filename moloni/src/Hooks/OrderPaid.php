<?php
namespace Moloni\Hooks;

use Moloni\Log;
use Moloni\Plugin;
use Moloni\Start;
use Moloni\Controllers\Documents;

class OrderPaid
{

    public $parent;

    /**
     * 
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_order_status_completed', [$this, 'documentCreate']);
    }

    public function documentCreate($orderId)
    {
        try {
            if (Start::login() && defined("INVOICE_AUTO") && INVOICE_AUTO) {
                Log::setFileName('DocumentsAuto');
                Log::write("A gerar automaticamente o documento da encomenda " . $orderId);

                $document = new Documents($orderId);
                $newDocument = $document->createDocument();

                if ($newDocument->getError()) {
                    Log::write("Houve um erro ao gerar o documento: " . strip_tags($newDocument->getError()->getDecodedMessage()));
                }
            }
        } catch (\Exception $ex) {
            Log::write("Falta error: " . $ex->getMessage());
        }
    }

}
