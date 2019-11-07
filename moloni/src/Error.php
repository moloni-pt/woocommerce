<?php

namespace Moloni;

use Exception;

class Error extends Exception
{
    /** @var array */
    private $request;

    /**
     * Throws a new error with a message and a log from the last request made
     * @param $message
     * @param bool $request
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message, $request = false, $code = 0, Exception $previous = null)
    {
        $this->request = $request ?: Curl::getLog();
        parent::__construct($message, $code, $previous);
    }

    public function showError()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $message = $this->getDecodedMessage();

        /** @noinspection PhpUnusedLocalVariableInspection */
        $url = $this->request['url'] ?: '';

        /** @noinspection PhpUnusedLocalVariableInspection */
        $sent = $this->request['sent'] ?: [];

        /** @noinspection PhpUnusedLocalVariableInspection */
        $received = $this->request['received'] ?: [];

        include MOLONI_TEMPLATE_DIR . 'Messages/DocumentError.php';
    }

    public function getError()
    {
        ob_start();
        $this->showError();
        return ob_get_clean();
    }

    /**
     * Returns the default error message from construct
     * Or tries to translate the error from Moloni API
     * @return string
     */
    public function getDecodedMessage()
    {
        $errorMessage = '<b>' . $this->getMessage() . '</b>';

        if (isset($this->request['received']) && is_array($this->request['received'])) {
            foreach ($this->request['received'] as $line) {
                if (isset($line['description'])) {
                    $errorMessage .= '<br>' . $this->translateMessage($line['description']);
                } elseif (isset($line[0]['description'])) {
                    $errorMessage .= '<br>' . $this->translateMessage($line[0]['description']);
                }
            }
        }

        return $errorMessage;
    }

    /**
     * @param string $message
     * @return string
     */
    private function translateMessage($message)
    {
        switch ($message) {
            case 'Field \'exemption_reason\' is required':
                $message = __('Um dos artigos não tem uma taxa de IVA associada e como tal, tem que seleccionar uma razão de isenção');
                break;
            case "Field 'category_id' must be integer, greater than 0" :
                $message = __('Verifique por favor se o artigo tem uma categoria associada');
                break;

        }

        return $message;
    }

    public function getRequest()
    {
        return $this->request;
    }
}