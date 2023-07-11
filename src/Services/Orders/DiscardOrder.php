<?php

namespace Moloni\Services\Orders;

use Moloni\Storage;
use WC_Order;

class DiscardOrder
{
    private $order;

    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    public function run()
    {
        $this->order->add_meta_data('_moloni_sent', '-1');
        $this->order->add_order_note(__('Encomenda marcada como gerada'));
        $this->order->save();
    }

    public function saveLog()
    {
        $message = sprintf(
            __('A encomenda foi descartada (%s)'),
            $this->order->get_order_number()
        );

        Storage::$LOGGER->info($message, [
            'tag' => 'service:order:discard',
            'order_id' => $this->order->get_id()
        ]);
    }
}
