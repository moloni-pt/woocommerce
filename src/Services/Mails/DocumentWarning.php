<?php

namespace Moloni\Services\Mails;

use Moloni\Services\Mails\Abstracts\MailAbstract;

class DocumentWarning extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni - Alerta de documento Moloni');
        $this->template = 'Emails/DocumentWarning.php';

        if (!empty($orderName)) {
            $this->extra = __('Encomenda') . ': ' . $orderName;
        }

        $this->run();
    }
}
