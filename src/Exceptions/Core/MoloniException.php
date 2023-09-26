<?php

namespace Moloni\Exceptions\Core;

use Exception;

class MoloniException extends Exception
{
    /**
     * Exception data
     *
     * @var array
     */
    private $data;

    /**
     * Throws a new error with a message
     *
     * @param string|null $message
     * @param array|null $data
     *
     * @return void
     */
    public function __construct($message = '', $data = [])
    {
        $this->data = $data;

        parent::__construct($message);
    }

    public function showError()
    {
        $message = $this->getDecodedMessage();
        $data = $this->data ?? [];

        include MOLONI_TEMPLATE_DIR . 'Exceptions/ExceptionError.php';
    }

    public function getError()
    {
        ob_start();
        $this->showError();
        return ob_get_clean();
    }

    public function getDecodedMessage(): string
    {
        return '<b>' . $this->getMessage() . '</b>';
    }

    public function getData(): array
    {
        return $this->data ?? [];
    }
}
