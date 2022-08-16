<?php

namespace Moloni\Services\Documents;

use Moloni\Curl;

class OpenDocument
{
    private $documentId;

    public function __construct($documentId)
    {
        $this->documentId = $documentId;

        $this->run();
    }

    public function run(): void
    {
        $props= [
            'document_id' => $this->documentId
        ];

        $invoice = Curl::simple('documents/getOne', $props);

        if (!isset($invoice['document_id'])) {
            return;
        }

        if ((int)$invoice['status'] === 1) {
            $url = Curl::simple('documents/getPDFLink', $props);

            $location = 'Location: ' . $url['url'];
        } else {
            $company = Curl::simple('companies/getOne', []);
            $slug = $company['slug'];

            $location = 'Location: https://moloni.pt/';
            $location .= $slug . '/' . $this->getDocumentTypeName($invoice['document_type']['saft_code']);
            $location .= '/showDetail/' . $invoice['document_id'];
        }

        header($location);
    }

    private function getDocumentTypeName($saftcode = ''): string
    {
        switch ($saftcode) {
            case 'FT' :
            default:
                $typeName = 'Faturas';
                break;
            case 'FR' :
                $typeName = 'FaturasRecibo';
                break;
            case 'FS' :
                $typeName = 'FaturaSimplificada';
                break;
            case 'PF' :
                $typeName = 'FaturasProForma';
                break;
            case 'GT' :
                $typeName = 'GuiasTransporte';
                break;
            case 'NEF' :
                $typeName = 'NotasEncomenda';
                break;
            case 'OR':
                $typeName = 'Orcamentos';
                break;
        }

        return $typeName;
    }
}
