<?php

namespace Moloni\Services\Mails;

use Moloni\Services\Mails\Abstracts\MailAbstract;

class DocumentFailed extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni - Criação de documento falhou');
        $this->template = 'Emails/DocumentFailed.php';

        if (!empty($orderName)) {
            $this->extra = __('Encomenda') . ': #' . $orderName;
        }

        $this->run();
    }
}
