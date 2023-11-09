<?php

namespace Moloni\Exceptions;

use Moloni\Curl;
use Moloni\Exceptions\Core\MoloniException;

class DocumentError extends MoloniException
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

    public function getDecodedMessage(): string
    {
        $errorMessage = '<b>' . $this->getMessage() . '</b>';

        if (isset($this->data['received']) && is_array($this->data['received'])) {
            foreach ($this->data['received'] as $line) {
                $message = $line['description'] ?? $line[0]['description'] ?? '';

                if (!empty($message)) {
                    $errorMessage .= '<br>' . $this->translateError($message);
                }
            }
        }

        return $errorMessage;
    }

    //             Privates             //

    protected function translateError(string $message): string
    {
        switch ($message) {
            case 'Field \'exemption_reason\' is required':
                $message = __('Um dos artigos não tem uma taxa de IVA associada e como tal, tem que seleccionar uma razão de isenção');
                break;
            case "Field 'category_id' must be integer, greater than 0":
                $message = __('Verifique por favor se o artigo tem uma categoria associada');
                break;
            case "Field 'document_set_wsat_id' must be valid":
                $message = __('Série de documento não está registada na AT');
                $message .= ' ';
                $message .= '(<a href="https://www.moloni.pt/suporte/guia-para-a-comunicacao-de-series" target="_blank">Ver FAQ</a>)';

                break;
        }

        return $message;
    }
}
