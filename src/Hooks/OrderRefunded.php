<?php

namespace Moloni\Hooks;

use Moloni\Start;
use Moloni\Storage;
use Moloni\Enums\Boolean;
use Moloni\Exceptions\ServiceException;
use Moloni\Services\Orders\CreateCreditNote;

class OrderRefunded
{
    public $parent;

    /**
     * Constructor
     *
     * @see https://wp-kama.com/plugin/woocommerce/hook/woocommerce_refund_created
     *
     * @param $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_refund_created', [$this, 'woocommerceRefundCreated']);
    }

    public function woocommerceRefundCreated(int $refundId, array $args)
    {
        if (!$this->isAuthed() || !$this->shouldRunHook()) {
            return;
        }

        $service = new CreateCreditNote($refundId, (bool)$args['restock_items']);

        try {
            $service->run();
            $service->saveLog();
        } catch (ServiceException $e) {
            $message = str_replace(
                '{0}',
                $service->getOrderNumber(),
                __('Houve um erro ao gerar reembolso ({0}).')
            );

            Storage::$LOGGER->critical($message, [
                'automatic:refund:create',
                'exception' => $e->getMessage(),
                'data' => $e->getData(),
            ]);
        }
    }

    //             Privates             //

    private function isAuthed(): bool
    {
        return Start::login(true);
    }

    private function shouldRunHook(): bool
    {
        return defined('CREATE_CREDIT_NOTE') && (int)CREATE_CREDIT_NOTE === Boolean::YES;
    }
}
