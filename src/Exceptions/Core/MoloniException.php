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
    protected $data;

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
        $this->data = $data;

        parent::__construct($message);
    }

    //             Publics             //

    public function getData(): array
    {
        return $this->data ?? [];
    }

    //             Error             //

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

    //             Privates             //

    protected function getDecodedMessage(): string
    {
        return '<b>' . $this->getMessage() . '</b>';
    }
}
