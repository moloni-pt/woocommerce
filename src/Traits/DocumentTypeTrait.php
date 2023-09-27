<?php

namespace Moloni\Traits;

use Moloni\Enums\DocumentTypes;

trait DocumentTypeTrait
{
    private function parseTypeNameFromSaftCode(string $saftcode): string
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
            case 'NC':
                $typeName = DocumentTypes::CREDIT_NOTES;
                break;
        }

        return $typeName;
    }
}
