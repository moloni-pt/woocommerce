<?php

namespace Moloni\Services\Documents;

use Moloni\Curl;
use Moloni\Exceptions\APIExeption;

class DownloadDocument
{
    private $documentId;

    public function __construct($documentId)
    {
        $this->documentId = $documentId;

        $this->run();
    }

    public function run(): void
    {
        try {
            $result = Curl::simple('documents/getPDFLink', [
                'document_id' => $this->documentId
            ]);

            if (isset($result['url'])) {
                $downloadUrl = 'https://www.moloni.pt/downloads/index.php?action=getDownload&';
                $downloadUrl .= substr($result['url'], strpos($result['url'], '?') + 1);
                $downloadUrl .= '&e=wordpress.auto.download@moloni.pt';
                $downloadUrl .= '&t=n';

                header('Location: ' . $downloadUrl);
            } else {
                $this->showError(_('Documento não existe'));
            }
        } catch (APIExeption $e) {
            $this->showError(_('Erro a obter documento'));
        }
    }

    private function showError($message): void
    {
        echo "<script>";
        echo "  alert('" . $message . "');";
        echo "  window.close();";
        echo "</script>";
    }
}
