<?php

namespace Moloni\Services\Mails;

use Moloni\Services\Mails\Abstracts\MailAbstract;

class CreditNoteFailed extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni - Criação de nota de crédito falhou');
        $this->template = 'Emails/CreditNoteFailed.php';

        if (!empty($orderName)) {
            $this->extra = __('Encomenda') . ': #' . $orderName;
        }

        $this->run();
    }
}
