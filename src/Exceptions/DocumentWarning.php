<?php

namespace Moloni\Exceptions;

use Moloni\Curl;
use Moloni\Exceptions\Core\MoloniException;

class DocumentWarning extends MoloniException
{
    /**
     * Throws a new error with a message
     *
     * @param string|null $message Exception message
     * @param array|null $data Exception data
     *
     * @return void
     */
    public function __construct($message = '', $data = [])
    {
        if (empty($data)) {
            $data = Curl::getLog();
        }

        parent::__construct($message, $data);
    }

    //             Overrides             //

    public function showError()
    {
        $message = $this->getDecodedMessage();

        $url = $this->data['url'] ?? '';
        $sent = $this->data['sent'] ?? [];
        $received = $this->data['received'] ?? [];

        include MOLONI_TEMPLATE_DIR . 'Exceptions/DocumentError.php';
    }
}
