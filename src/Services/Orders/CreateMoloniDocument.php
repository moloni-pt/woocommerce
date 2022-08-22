<?php

namespace Moloni\Services\Orders;

use Moloni\Warning;
use WC_Order;
use Moloni\Curl;
use Moloni\Enums\Boolean;
use Moloni\Enums\DocumentStatus;
use Moloni\Error;
use Moloni\Enums\DocumentTypes;
use Moloni\Controllers\Documents;

class CreateMoloniDocument
{
    /**
     * Order object
     *
     * @var WC_Order
     */
    private $order;

    /**
     * Created document id
     *
     * @var int
     */
    private $documentId = 0;

    /**
     * Document type
     *
     * @var string|null
     */
    private $documentType;

    public function __construct($orderId)
    {
        $this->order = new WC_Order((int)$orderId);
        $this->documentType = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : null;
    }

    /**
     * Run service
     *
     * @throws Warning
     * @throws Error
     */
    public function run(): void
    {
        $this->checkForWarnings();

        $company = Curl::simple('companies/getOne', []);

        if ($this->shouldCreateBillOfLading()) {
            $billOfLading = new Documents($this->order, $company);
            $billOfLading
                ->setDocumentType(DocumentTypes::BILLS_OF_LADING)
                ->setDocumentStatus(DocumentStatus::CLOSED)
                ->setSendEmail(Boolean::NO)
                ->createDocument();
        }

        if (isset($billOfLading)) {
            $builder = clone $billOfLading;
            $builder
                ->setDocumentType($this->documentType)
                ->setDocumentStatus()
                ->setSendEmail()
                ->addAssociatedDocument(
                    $billOfLading->getDocumentId(),
                    $billOfLading->getDocumentTotal(),
                    $billOfLading->getDocumentProducts()
                );

            unset($billOfLading);
        } else {
            $builder = new Documents($this->order, $company);
            $builder->setDocumentType($this->documentType);
        }

        $builder
            ->createDocument();

        $this->documentId = $builder->getDocumentId();
    }

    //          GETS          //

    public function getDocumentId(): int
    {
        return (int)$this->documentId;
    }

    public function getOrderID(): int
    {
        return (int)$this->order->get_id();
    }

    public function getOrderNumber(): string
    {
        return $this->order->get_order_number() ?? '';
    }

    //          PRIVATES          //

    private function shouldCreateBillOfLading(): bool
    {
        $createBillOfLading = Boolean::NO;

        if (defined('CREATE_BILL_OF_LADING')) {
            $createBillOfLading = CREATE_BILL_OF_LADING;
        }

        return $this->documentType !== DocumentTypes::BILLS_OF_LADING && (bool)$createBillOfLading === true;
    }

    private function isReferencedInDatabase(): bool
    {
        return (bool)$this->order->get_meta('_moloni_sent');
    }

    /**
     * Checks if order already has a document associated
     *
     * @throws Error
     */
    private function checkForWarnings(): void
    {
        if ((!isset($_GET['force']) || sanitize_text_field($_GET['force']) !== 'true') && $this->isReferencedInDatabase()) {
            $forceUrl = 'admin.php?page=moloni&action=genInvoice&id=' . $this->getOrderID() . '&force=true';

            if (!empty($this->documentType)) {
                $forceUrl .= '&document_type=' . sanitize_text_field($this->documentType);
            }

            throw new Error(
                __('O documento da encomenda ' . $this->order->get_order_number() . ' já foi gerado anteriormente!') .
                " <a href='$forceUrl'>" . __('Gerar novamente') . '</a>'
            );
        }
    }
}
