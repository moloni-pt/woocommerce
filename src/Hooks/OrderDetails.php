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

    public $document = [];

    public $documentId = 0;

    public $documentUrl = '';

    public $documentName = '';

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

        $this->getDocumentId();

        if ($this->documentId <= 0) {
            return;
        }

        $this->getDocumentData();

        if (empty($this->document) || (int)$this->document['status'] !== DocumentStatus::CLOSED) {
            return;
        }

        $this->getDocumentUrl();

        if (empty($this->documentUrl)) {
            return;
        }

        $this->getDocumentTypeName();
        $this->getHtmlToRender();

        apply_filters('moloni_before_order_details_render', $this);

        if (!empty($this->htmlToRender)) {
            echo $this->htmlToRender;
        }
    }

    private function getDocumentId(): void
    {
        $this->documentId = MoloniOrder::getLastCreatedDocument($this->order);
    }

    private function getDocumentUrl(): void
    {
        try {
            $result = Curl::simple('documents/getPDFLink', ['document_id' => $this->documentId]);

            if (isset($result['url'])) {
                $this->documentUrl = $result['url'];
            }
        } catch (Error $e) {
        }
    }

    private function getDocumentData(): void
    {
        try {
            $invoice = Curl::simple('documents/getOne', ['document_id' => $this->documentId]);

            if (isset($invoice['document_id'])) {
                $this->document = $invoice;
            }
        } catch (Error $e) {
        }
    }

    private function getDocumentTypeName(): void
    {
        $typeName = $this->parseTypeNameFromSaftCode($this->document['document_type']['saft_code'] ?? '');

        $this->documentName = DocumentTypes::getDocumentTypeName($typeName);
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
                <li>
                    <a href="<?= $this->documentUrl ?>" target="_blank">
                        <?= __(empty($this->documentName) ? 'Fatura' : $this->documentName) ?>
                    </a>
                </li>
            </ul>
        </section>
        <?php

        $this->htmlToRender = ob_get_clean();
    }
}
