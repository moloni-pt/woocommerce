<?php

namespace Moloni\Hooks;

use Exception;
use Moloni\Start;
use Moloni\Storage;
use Moloni\Enums\Boolean;
use Moloni\Exceptions\DocumentError;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Exceptions\DocumentProcessStopped;
use Moloni\Services\Mails\CreditNoteFailed;
use Moloni\Services\Mails\CreditNoteWarning;
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

        add_action('woocommerce_refund_created', [$this, 'woocommerceRefundCreated'], 99, 2);
    }

    public function woocommerceRefundCreated($refundId, $args)
    {
        if (!$this->isAuthed() || !$this->shouldRunHook()) {
            return;
        }

        $service = new CreateCreditNote($refundId, (bool)$args['restock_items']);

        try {
            $service->run();
            $service->saveLog();
        } catch (DocumentProcessStopped $e) {
            $orderName = $service->getOrderNumber();

            /**
             * Save plugin log
             */

            $message = __('Processamento de reembolso bloqueado');
            $message .= ' (' . $orderName . ')';
            $message .= '.';

            Storage::$LOGGER->info($message, [
                'tag' => 'automatic:refund:create:stopped',
                'exception' => $e->getMessage(),
                'data' => $e->getData(),
            ]);
        } catch (DocumentError $e) {
            $order = $service->getOrder();
            $orderName = $service->getOrderNumber();

            /**
             * Save plugin log
             */

            $message = __('Houve um erro ao gerar reembolso');
            $message .= ' (' . $orderName . ')';
            $message .= '.';

            Storage::$LOGGER->error($message, [
                'tag' => 'automatic:refund:create:error',
                'exception' => $e->getMessage(),
                'data' => $e->getData(),
            ]);

            /**
             * Add custom note to order
             */

            if (!empty($order)) {
                $note = __('Erro ao gerar nota de crédito automaticamente.');
                $note .= ' ';
                $note .= __('Consulte os registos para mais informações.');

                $order->add_order_note($note);
            }

            /**
             * Send alert e-mail
             */

            if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
                new CreditNoteFailed(ALERT_EMAIL, $orderName);
            }
        } catch (DocumentWarning $e) {
            $order = $service->getOrder();
            $orderName = $service->getOrderNumber();

            /**
             * Save plugin log
             */

            $message = __('Houve um alerta ao gerar reembolso');
            $message .= ' (' . $orderName . ')';
            $message .= '.';

            Storage::$LOGGER->warning($message, [
                'tag' => 'automatic:refund:create:warning',
                'exception' => $e->getMessage(),
                'data' => $e->getData(),
                'results' => $service->getResults()
            ]);

            /**
             * Add custom note to order
             */

            $note = __('Alerta ao gerar nota de crédito automaticamente.');
            $note .= ' ';
            $note .= __('Consulte os registos para mais informações.');

            $order->add_order_note($note);

            /**
             * Send alert e-mail
             */

            if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
                new CreditNoteWarning(ALERT_EMAIL, $orderName);
            }
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__('Fatal error'), [
                'tag' => 'automatic:refund:create:fatalerror',
                'exception' => $e->getMessage(),
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
