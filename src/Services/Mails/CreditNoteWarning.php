<?php

namespace Moloni\Services\Mails;

use Moloni\Services\Mails\Abstracts\MailAbstract;

class CreditNoteWarning extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni - Alerta de nota de crédito Moloni');
        $this->template = 'Emails/CreditNoteWarning.php';

        if (!empty($orderName)) {
            $this->extra = __('Encomenda') . ': #' . $orderName;
        }

        $this->run();
    }
}
