<?php

namespace Moloni\Services\Documents;

use Moloni\Curl;
use Moloni\Enums\DocumentTypes;
use Moloni\Traits\DocumentTypeTrait;

class OpenDocument
{
    use DocumentTypeTrait;

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
            $location .= $slug . '/' . $this->getDocumentTypeSlug($invoice['document_type']['saft_code']);
            $location .= '/showDetail/' . $invoice['document_id'];
        }

        header($location);
    }

    protected function getDocumentTypeSlug(string $saftcode = ''): string
    {
        $typeName = $this->parseTypeNameFromSaftCode($saftcode);

        return DocumentTypes::getDocumentTypeSlug($typeName);
    }
}
