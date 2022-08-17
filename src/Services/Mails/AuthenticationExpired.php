<?php

namespace Moloni\Services\Mails;

use Moloni\Services\Mails\Abstracts\MailAbstract;

class AuthenticationExpired extends MailAbstract
{
    public function __construct($to = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni - Autenticação expirada');
        $this->template = 'Emails/AuthenticationExpired.php';

        $this->run();
    }
}
