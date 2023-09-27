<?php

namespace Moloni\Hooks;

use WC_Order;
use Moloni\Curl;
use Moloni\Error;
use Moloni\Start;
use Moloni\Plugin;
use Moloni\Enums\Boolean;
use Moloni\Enums\DocumentTypes;
use Moloni\Enums\DocumentStatus;
use Moloni\Helpers\MoloniOrder;
use Moloni\Traits\DocumentTypeTrait;

class OrderDetails
{
    use DocumentTypeTrait;

    public $parent;

    public $order;

    public $documents = [];

    public $htmlToRender = '';

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_order_details_after_customer_details', [$this, 'orderDetailsAfterCustomerDetails']);
    }

    public function orderDetailsAfterCustomerDetails(WC_Order $order)
    {
        $this->order = $order;

        if (!Start::login(true)) {
            return;
        }

        if (!defined('MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW') || (int)MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW === Boolean::NO) {
            return;
        }

        $this->loadDocuments();

        if (empty($this->documents)) {
            return;
        }

        $this->getHtmlToRender();

        apply_filters('moloni_before_order_details_render', $this);

        if (!empty($this->htmlToRender)) {
            echo $this->htmlToRender;
        }
    }

    private function loadDocuments(): void
    {
        $documentIds = [];

        $lastCreatedDocument = MoloniOrder::getLastCreatedDocument($this->order);

        if (!empty($lastCreatedDocument)) {
            $documentIds[] = $lastCreatedDocument;
        }

        $allCreatedCreditNotes = MoloniOrder::getAllCreatedCreditNotes($this->order);

        if (!empty($allCreatedCreditNotes)) {
            $documentIds = array_merge($documentIds, $allCreatedCreditNotes);
        }

        if (empty($documentIds)) {
            return;
        }

        foreach ($documentIds as $documentId) {
            $documentData = $this->getDocumentData($documentId);

            if (empty($documentData) || $documentData['status'] !== DocumentStatus::CLOSED) {
                continue;
            }

            $documentPdfLink = $this->getDocumentUrl($documentId);

            if (empty($documentPdfLink)) {
                continue;
            }

            $documentTypeName = $this->getDocumentTypeName($documentData['document_type']['saft_code'] ?? '');

            if (empty($documentTypeName)) {
                continue;
            }

            $this->documents[] = [
                'label' => __($documentTypeName),
                'href' => $documentPdfLink,
                'data' => $documentData
            ];
        }
    }

    private function getDocumentUrl(int $documentId): string
    {
        try {
            $result = Curl::simple('documents/getPDFLink', ['document_id' => $documentId]);

            if (isset($result['url'])) {
                return $result['url'];
            }
        } catch (Error $e) {
        }

        return '';
    }

    private function getDocumentData(int $documentId): array
    {
        try {
            $invoice = Curl::simple('documents/getOne', ['document_id' => $documentId]);

            if (isset($invoice['document_id'])) {
                return $invoice;
            }
        } catch (Error $e) {
        }

        return [];
    }

    private function getDocumentTypeName(string $saftCode): string
    {
        $typeName = $this->parseTypeNameFromSaftCode($saftCode);

        return DocumentTypes::getDocumentTypeName($typeName);
    }

    private function getHtmlToRender(): void
    {
        ob_start();

        ?>
        <section id="invoice_document">
            <h2>
                <?= __('Documento de faturação') ?>
            </h2>
            <ul>
                <?php foreach ($this->documents as $document) : ?>
                    <li>
                        <a href="<?= $document['href'] ?>" target="_blank">
                            <?= $document['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php

        $this->htmlToRender = ob_get_clean();
    }
}
