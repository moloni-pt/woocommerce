<?php

namespace Moloni\Services\Documents;

use Moloni\Curl;
use Moloni\Enums\DocumentTypes;

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
                $typeName = DocumentTypes::INVOICES;
                break;
            case 'FR' :
                $typeName = DocumentTypes::INVOICE_RECEIPTS;
                break;
            case 'FS' :
                $typeName = DocumentTypes::SIMPLIFIED_INVOICES;
                break;
            case 'PF' :
                $typeName = DocumentTypes::PRO_FORMA_INVOICES;
                break;
            case 'GT' :
                $typeName = DocumentTypes::BILLS_OF_LADING;
                break;
            case 'NEF' :
                $typeName = DocumentTypes::PURCHASE_ORDER;
                break;
            case 'OR':
                $typeName = DocumentTypes::ESTIMATES;
                break;
        }

        return DocumentTypes::getDocumentTypeSlug($typeName);
    }
}
