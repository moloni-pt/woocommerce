<?php

namespace Moloni\Enums;

class DocumentTypes
{
    public const INVOICES = 'invoices';
    public const INVOICE_RECEIPTS = 'invoiceReceipts';
    public const PURCHASE_ORDER = 'purchaseOrder';
    public const PRO_FORMA_INVOICES = 'proFormaInvoices';
    public const SIMPLIFIED_INVOICES = 'simplifiedInvoices';
    public const ESTIMATES = 'estimates';
    public const BILLS_OF_LADING = 'billsOfLading';
    public const CREDIT_NOTES = 'creditNotes';

    public const TYPES_WITH_PAYMENTS = [
        self::INVOICE_RECEIPTS,
        self::PRO_FORMA_INVOICES,
        self::SIMPLIFIED_INVOICES,
    ];

    public const TYPES_REQUIRES_DELIVERY = [
        self::BILLS_OF_LADING,
    ];

    public const TYPES_CONVERTS_TO_CREDIT_NOTES = [
        self::INVOICES,
        self::INVOICE_RECEIPTS,
        self::SIMPLIFIED_INVOICES,
    ];

    public const TYPES_SELF_PAID = [
        self::SIMPLIFIED_INVOICES,
        self::INVOICE_RECEIPTS,
    ];

    public const TYPES_NAMES = [
        self::INVOICES => 'Fatura',
        self::INVOICE_RECEIPTS => 'Fatura-Recibo',
        self::PURCHASE_ORDER => 'Nota de Encomenda',
        self::PRO_FORMA_INVOICES => 'Fatura Pró-Forma',
        self::SIMPLIFIED_INVOICES => 'Fatura Simplificada',
        self::ESTIMATES => 'Orçamento',
        self::BILLS_OF_LADING => 'Guia de Transporte',
        self::CREDIT_NOTES => 'Nota de Crédito',
    ];

    public const TYPES_SLUGS = [
        self::INVOICES => 'Faturas',
        self::INVOICE_RECEIPTS => 'FaturasRecibo',
        self::PURCHASE_ORDER => 'NotasEncomenda',
        self::PRO_FORMA_INVOICES => 'FaturasProForma',
        self::SIMPLIFIED_INVOICES => 'FaturaSimplificada',
        self::ESTIMATES => 'Orcamentos',
        self::BILLS_OF_LADING => 'GuiasTransporte',
        self::CREDIT_NOTES => 'NotasCredito',
    ];

    public static function getDocumentTypeById(int $documentTypeId): string
    {
        switch ($documentTypeId) {
            case 1:
                return self::INVOICES;
            case 27:
                return self::INVOICE_RECEIPTS;
            case 28:
                return self::PURCHASE_ORDER;
            case 13:
                return self::PRO_FORMA_INVOICES;
            case 20:
                return self::SIMPLIFIED_INVOICES;
            case 14:
                return self::ESTIMATES;
            case 15:
                return self::BILLS_OF_LADING;
            case 3:
                return self::CREDIT_NOTES;
        }

        return '';
    }

    public static function getDocumentTypeForRender(): array
    {
        return [
            self::INVOICES => 'Fatura',
            self::INVOICE_RECEIPTS => 'Factura/Recibo',
            self::PURCHASE_ORDER => 'Nota de Encomenda',
            self::PRO_FORMA_INVOICES => 'Fatura Pró-Forma',
            self::SIMPLIFIED_INVOICES => 'Factura Simplificada',
            self::ESTIMATES => 'Orçamento',
            self::BILLS_OF_LADING => 'Guia de Transporte',
        ];
    }

    public static function getDocumentTypeSlug(?string $documentType = ''): string
    {
        return self::TYPES_SLUGS[$documentType] ?? '';
    }

    public static function getDocumentTypeName(?string $documentType = ''): string
    {
        return self::TYPES_NAMES[$documentType] ?? '';
    }

    public static function hasPayments(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_WITH_PAYMENTS, true);
    }

    public static function requiresDelivery(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_REQUIRES_DELIVERY, true);
    }

    public static function canConvertToCreditNote(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_CONVERTS_TO_CREDIT_NOTES, true);
    }

    public static function isSelfPaid(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_SELF_PAID, true);
    }
}
