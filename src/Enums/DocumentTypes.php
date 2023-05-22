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

    public const TYPES_WITH_PAYMENTS = [
        self::INVOICE_RECEIPTS,
        self::PRO_FORMA_INVOICES,
        self::SIMPLIFIED_INVOICES,
    ];

    public const TYPES_REQUIRES_DELIVERY = [
        self::BILLS_OF_LADING,
    ];

    public const TYPES_NAMES = [
        self::INVOICES => 'Fatura',
        self::INVOICE_RECEIPTS => 'Factura/Recibo',
        self::PURCHASE_ORDER => 'Nota de Encomenda',
        self::PRO_FORMA_INVOICES => 'Fatura Pró-Forma',
        self::SIMPLIFIED_INVOICES => 'Factura Simplificada',
        self::ESTIMATES => 'Orçamento',
        self::BILLS_OF_LADING => 'Guia de Transporte',
    ];

    public const TYPES_SLUGS = [
        self::INVOICES => 'Faturas',
        self::INVOICE_RECEIPTS => 'FaturasRecibo',
        self::PURCHASE_ORDER => 'NotasEncomenda',
        self::PRO_FORMA_INVOICES => 'FaturasProForma',
        self::SIMPLIFIED_INVOICES => 'FaturaSimplificada',
        self::ESTIMATES => 'Orcamentos',
        self::BILLS_OF_LADING => 'GuiasTransporte',
    ];

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
}
