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

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_order_details_after_customer_details', [$this, 'orderDetailsAfterCustomerDetails']);
    }

    public function orderDetailsAfterCustomerDetails(WC_Order $order)
    {
        if (!Start::login(true)) {
            return;
        }

        if (!defined('MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW') || (int)MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW === Boolean::NO) {
            return;
        }

        $documentId = MoloniOrder::getLastCreatedDocument($order);

        if ($documentId <= 0) {
            return;
        }

        $document = $this->getDocumentData($documentId);

        if (empty($document) || (int)$document['status'] !== DocumentStatus::CLOSED) {
            return;
        }

        $href = $this->getDocumentUrl($documentId);

        if (empty($href)) {
            return;
        }

        $documentName = $this->getDocumentTypeName($document['document_type']['saft_code']);

        ?>
        <div id="invoice_document">
            <h2>
                <?= __('Documento de faturação') ?>
            </h2>
            <ul>
                <li>
                    <a href="<?= $href ?>" target="_blank">
                        <?= __(empty($documentName) ? 'Fatura' : $documentName) ?>
                    </a>
                </li>
            </ul>
        </div>
        <?php
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

    private function getDocumentTypeName(string $saftcode = ''): string
    {
        $typeName = $this->parseTypeNameFromSaftCode($saftcode);

        return DocumentTypes::getDocumentTypeName($typeName);
    }
}
