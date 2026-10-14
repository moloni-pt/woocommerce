<?php

namespace Moloni\Services\Orders;

use Moloni\Exceptions\APIException;
use Moloni\Exceptions\DocumentError;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Helpers\Security;
use WC_Order;
use Moloni\Curl;
use Moloni\Enums\Boolean;
use Moloni\Enums\DocumentStatus;
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

    /**
     * Create the document even if the order already has one
     *
     * @var bool
     */
    private $force;

    /**
     * @param int $orderId
     * @param string|null $documentType Overrides the document type from settings (manual generation only)
     * @param bool $force Create the document even if the order already has one (manual generation only)
     */
    public function __construct($orderId, ?string $documentType = null, bool $force = false)
    {
        $this->order = new WC_Order((int)$orderId);
        $this->force = $force;

        if (!empty($documentType) && array_key_exists($documentType, DocumentTypes::getDocumentTypeForRender())) {
            $this->documentType = $documentType;
        }
    }

    /**
     * Run service
     *
     * @throws DocumentError
     * @throws DocumentWarning
     */
    public function run(): void
    {
        $this->checkForWarnings();

        try {
            $company = Curl::simple('companies/getOne', []);
        } catch (APIException $e) {
            throw new DocumentError(__('Erro a obter empresa'), $e->getData());
        }

        if (empty($company)) {
            throw new DocumentError(__('Erro a obter empresa'));
        }

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
        if ($this->documentType === DocumentTypes::BILLS_OF_LADING) {
            return false;
        }

        if (!defined('DOCUMENT_STATUS') || (int)DOCUMENT_STATUS === DocumentStatus::DRAFT) {
            return false;
        }

        if (!defined('CREATE_BILL_OF_LADING')) {
            return false;
        }

        return (bool)CREATE_BILL_OF_LADING;
    }

    private function isReferencedInDatabase(): bool
    {
        return (bool)$this->order->get_meta('_moloni_sent');
    }

    /**
     * Checks if order already has a document associated
     *
     * @throws DocumentError
     */
    private function checkForWarnings(): void
    {
        if (!$this->force && $this->isReferencedInDatabase()) {
            $forceUrl = 'admin.php?page=moloni&action=genInvoice&id=' . $this->getOrderID() . '&force=true';

            if (!empty($this->documentType)) {
                $forceUrl .= '&document_type=' . $this->documentType;
            }

            $forceUrl = esc_url(Security::getNonceUrl(admin_url($forceUrl)));

            throw new DocumentError(
                __('O documento da encomenda ' . $this->order->get_order_number() . ' já foi gerado anteriormente!') .
                " <a href='$forceUrl'>" . __('Gerar novamente') . '</a>'
            );
        }
    }
}
