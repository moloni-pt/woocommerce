<?php

namespace Moloni\Services\Orders;

use WC_Order;
use WC_Product;
use WC_Order_Refund;
use Moloni\Curl;
use Moloni\Error;
use Moloni\Tools;
use Moloni\Storage;
use Moloni\Helpers\MoloniOrder;
use Moloni\Enums\Boolean;
use Moloni\Enums\DocumentTypes;
use Moloni\Enums\DocumentStatus;
use Moloni\Exceptions\ServiceException;

class CreateCreditNote
{
    /**
     * Order object
     *
     * @var WC_Order
     */
    private $order;

    /**
     * Order refund object
     *
     * @var WC_Order_Refund
     */
    private $refund;

    /**
     * Flag to see if stock should be refunded
     *
     * @var bool
     */
    private $restockItems;

    /**
     * Service results
     *
     * @var array
     */
    private $results = [];


    private $originalDocument = [];

    private $originalDocumentType = '';

    private $originalUnrelatedProducts = [];

    private $unrelatedProducts = [];


    public function __construct(int $refundId, bool $restockItems = true)
    {
        $this->refund = new WC_Order_Refund($refundId);
        $this->restockItems = $restockItems;

        $this->order = wc_get_order($this->refund->get_parent_id());
    }

    //          Actions          //

    /**
     * Action runner
     *
     * @throws ServiceException
     */
    public function run()
    {
        $documentId = MoloniOrder::getLastCreatedDocument($this->order);

        if (empty($documentId) || $documentId < 0) {
            throw new ServiceException('Order does not have any document created');
        }

        try {
            $this->originalDocument = Curl::simple('documents/getOne', ['document_id' => $documentId]);
        } catch (Error $e) {
            throw new ServiceException('Error fetching document', [
                'request' => $e->getRequest()
            ]);
        }

        $this->validateDocument();

        try {
            $this->originalUnrelatedProducts = $this->unrelatedProducts = Curl::simple('documents/getUnrelatedProducts', ['document_id' => $documentId]);
        } catch (Error $e) {
            throw new ServiceException('Error fetching unrelated products', [
                'request' => $e->getRequest()
            ]);
        }

        $this->validateUnrelatedProducts();

        $creditNoteProps = [];

        $this->setBasics($creditNoteProps);
        $this->setProducts($creditNoteProps);
        $this->setShipping($creditNoteProps);

        try {
            $mutation = Curl::simple(DocumentTypes::CREDIT_NOTES . '/insert', $creditNoteProps);
        } catch (Error $e) {
            throw new ServiceException('Error creating document', [
                'request' => $e->getRequest()
            ]);
        }

        if (empty($mutation) || !isset($mutation['document_id'])) {
            throw new ServiceException('Error creating document', [
                'props' => $creditNoteProps,
                'mutation' => $mutation
            ]);
        }

        $this->results = [
            'tag' => 'automatic:refund:create',
            'refund_id' => $this->refund->get_id(),
            'order_id' => $this->order->get_id(),
            'document_id' => $mutation['document_id'],
            'document_status' => (int)CREDIT_NOTE_DOCUMENT_STATUS,
        ];

        $this->saveRecord();
    }

    public function saveLog()
    {
        $documentTypeName = DocumentTypes::getDocumentTypeName(DocumentTypes::CREDIT_NOTES);

        /**
         * Save plugin log
         */

        $message = __('{0} foi gerado com sucesso ({1})');
        $message = str_replace('{0}', $documentTypeName, $message);
        $message = str_replace('{1}', $this->order->get_order_number(), $message);

        Storage::$LOGGER->info($message, $this->results);

        /**
         * Add custom note to order
         */

        $note = __('Documento inserido no Moloni');
        $note .= " (" . $documentTypeName . ")";

        $this->order->add_order_note($note);
    }

    //          SETS          //

    /**
     * Set basic props
     */
    private function setBasics(array &$creditNoteProps): void
    {
        $refundedTotal = abs($this->refund->get_total());

        $creditNoteProps = [
            'date' => date('Y-m-d'),
            'customer_id' => (int)$this->originalDocument['customer_id'],
            'our_reference' => $this->originalDocument['our_reference'],
            'your_reference' => $this->originalDocument['your_reference'],
            'document_set_id' => (int)CREDIT_NOTE_DOCUMENT_SET_ID,
            'status' => (int)CREDIT_NOTE_DOCUMENT_STATUS,
            'associated_documents' => [],
            'products' => [],
            'send_email' => [],
        ];

        if ($this->shouldSendByEmail()) {
            $email = $this->order->get_billing_email() ?? '';

            $name = $this->order->get_billing_first_name() ?? '';
            $name .= ' ';
            $name .= $this->order->get_billing_last_name() ?? '';
            $name = trim($name);

            if (empty($name)) {
                $name = $this->originalDocument['entity_name'] ?? '';
            }

            if (!empty($email) && !empty($name)) {
                $creditNoteProps['send_email'][] = [
                    'email' => $email,
                    'name' => $name,
                    'msg' => ''
                ];
            }
        }

        if (!empty($this->originalDocument['exchange_currency_id']) && !empty($this->originalDocument['exchange_rate'])) {
            $refundedTotal /= $this->originalDocument['exchange_rate'];

            $creditNoteProps['exchange_rate'] = $this->originalDocument['exchange_rate'];
            $creditNoteProps['exchange_currency_id'] = $this->originalDocument['exchange_currency_id'];
        }

        $creditNoteProps['net_value'] = $refundedTotal;

        $creditNoteProps['associated_documents'][] = [
            'associated_id' => $this->originalDocument['document_id'],
            'value' => DocumentTypes::isSelfPaid($this->originalDocumentType) ? 0 : $refundedTotal
        ];
    }

    /**
     * Set products
     *
     * @throws ServiceException
     */
    private function setProducts(array &$creditNoteProps): void
    {
        $refundedItems = $this->refund->get_items();

        if (empty($refundedItems)) {
            return;
        }

        foreach ($refundedItems as $refundedItem) {
            $refundedQty = abs($refundedItem->get_quantity());
            $refundedPrice = abs($refundedItem->get_subtotal()) / $refundedQty;

            /** @var WC_Product|bool $wcProduct */
            $wcProduct = $refundedItem->get_product();

            if (empty($wcProduct)) {
                throw new ServiceException('Refunded product does not exist in Wordpress', [
                    'name' => $refundedItem->get_name(),
                    'qty' => $refundedQty,
                    'unrelatedProducts' => $this->originalUnrelatedProducts,
                ]);
            }

            $matchedDocumentProduct = $this->tryToMatchProduct($wcProduct, $refundedQty);

            if (empty($matchedDocumentProduct)) {
                throw new ServiceException('Refunded product not matched in document unrelated products', [
                    'name' => $refundedItem->get_name(),
                    'qty' => $refundedQty,
                    'unrelatedProducts' => $this->originalUnrelatedProducts,
                ]);
            }

            if (!empty($this->originalDocument['exchange_currency_id']) && !empty($this->originalDocument['exchange_rate'])) {
                $refundedPrice /= $this->originalDocument['exchange_rate'];
            }

            if ($matchedDocumentProduct['discount'] > 0) {
                $refundedPrice = $refundedPrice / ($matchedDocumentProduct['discount'] / 100);
            }

            if (abs($refundedPrice - $matchedDocumentProduct['price']) < 0.02) {
                $refundedPrice = $matchedDocumentProduct['price'];
            }

            if ($refundedPrice > $matchedDocumentProduct['price']) {
                throw new ServiceException('Refunded value is bigger than the document product price', [
                    'name' => $refundedItem->get_name(),
                    'qty' => $refundedQty,
                    'price' => $refundedPrice,
                    'matchedDocumentProduct' => $matchedDocumentProduct,
                ]);
            }

            $newProduct = [
                'product_id' => $matchedDocumentProduct['product_id'],
                'name' => $matchedDocumentProduct['name'],
                'summary' => $matchedDocumentProduct['summary'],
                'exemption_reason' => $matchedDocumentProduct['exemption_reason'],
                'taxes' => $matchedDocumentProduct['taxes'],
                'related_id' => $matchedDocumentProduct['document_product_id'],
                'warehouse_id' => $matchedDocumentProduct['warehouse_id'],
                'origin_id' => $this->originalDocument['document_id'],
                'has_stock' => (int)$this->restockItems,
                'qty' => $refundedQty,
                'price' => $refundedPrice,
                'discount' => $matchedDocumentProduct['discount'],
            ];

            $creditNoteProps['products'][] = $newProduct;
        }
    }

    /**
     * Set shipping
     *
     * @throws ServiceException
     */
    private function setShipping(array &$creditNoteProps): void
    {
        $refundedShippingValue = abs($this->refund->get_shipping_total());

        if ($refundedShippingValue > 0) {
            $matchedDocumentShipping = $this->tryToMatchShipping();

            if (empty($matchedDocumentShipping)) {
                throw new ServiceException('Shipping product not found in document', [
                    'creditNoteProps' => $creditNoteProps,
                    'unrelatedProducts' => $this->originalUnrelatedProducts,
                ]);
            }

            if (!empty($this->originalDocument['exchange_currency_id']) && !empty($this->originalDocument['exchange_rate'])) {
                $refundedShippingValue /= $this->originalDocument['exchange_rate'];
            }

            if (abs($refundedShippingValue - $matchedDocumentShipping['price']) < 0.02) {
                $refundedShippingValue = $matchedDocumentShipping['price'];
            }

            if ($refundedShippingValue > $matchedDocumentShipping['price']) {
                throw new ServiceException('Refunded value is bigger than the document shipping price', [
                    'price' => $refundedShippingValue,
                    'matchedDocumentShipping' => $matchedDocumentShipping,
                ]);
            }

            $creditNoteProps['products'][] = array(
                'product_id' => $matchedDocumentShipping['product_id'],
                'name' => $matchedDocumentShipping['name'],
                'summary' => $matchedDocumentShipping['summary'],
                'exemption_reason' => $matchedDocumentShipping['exemption_reason'],
                'taxes' => $matchedDocumentShipping['taxes'],
                'related_id' => $matchedDocumentShipping['document_product_id'],
                'warehouse_id' => $matchedDocumentShipping['warehouse_id'],
                'origin_id' => $this->originalDocument['document_id'],
                'has_stock' => (int)$this->restockItems,
                'qty' => $matchedDocumentShipping['qty'],
                'price' => $refundedShippingValue,
            );
        }
    }

    //          GETS          //

    public function getOrder()
    {
        return $this->order ?? null;
    }

    public function getOrderID(): int
    {
        if (empty($this->order)) {
            return 0;
        }

        return $this->order->get_id();
    }

    //          Privates          //

    private function saveRecord()
    {
        $this->order->add_meta_data('_moloni_credit_note', $this->results['document_id']);
        $this->order->save();
    }

    private function tryToMatchShipping(): array
    {
        foreach ($this->unrelatedProducts as $key => $unrelatedProduct) {
            if (strtolower($unrelatedProduct['reference']) === 'portes') {
                unset($this->unrelatedProducts[$key]);

                return $unrelatedProduct;
            }
        }

        return [];
    }

    private function tryToMatchProduct(WC_Product $wcProduct, int $refundedQty): array
    {
        $wcProductReference = $wcProduct->get_sku();

        /** Same as we do when creating a product in Moloni */
        if (empty($wcProductReference)) {
            $wcProductReference = Tools::createReferenceFromString($wcProduct->get_name(), $wcProduct->get_id());
            $wcProductReference = mb_substr($wcProductReference, 0, 30);
        }

        foreach ($this->unrelatedProducts as $key => $unrelatedProduct) {
            if ($wcProductReference !== $unrelatedProduct['reference']) {
                continue;
            }

            if ($refundedQty > $unrelatedProduct['qty']) {
                continue;
            }

            $this->unrelatedProducts[$key]['qty'] -= $refundedQty;

            if (empty($this->unrelatedProducts[$key]['qty'])) {
                unset($this->unrelatedProducts[$key]);
            }

            return $unrelatedProduct;
        }

        return [];
    }

    //          Validations          //

    /**
     * Check if document can be used to associate to credit note
     *
     * @throws ServiceException
     */
    private function validateDocument()
    {
        if (empty($this->originalDocument) || !isset($this->originalDocument['document_id'])) {
            throw new ServiceException('Document not found in current Moloni company');
        }

        if ((int)$this->originalDocument['status'] !== DocumentStatus::CLOSED) {
            throw new ServiceException('Document is not closed');
        }

        $this->originalDocumentType = DocumentTypes::getDocumentTypeById((int)($this->originalDocument['document_type']['document_type_id'] ?? 0));

        if (empty($this->originalDocumentType) || !DocumentTypes::canConvertToCreditNote($this->originalDocumentType)) {
            throw new ServiceException('Target document cannot be converted do credit note');
        }
    }

    /**
     * Check if unrelated products are valid
     *
     * @throws ServiceException
     */
    private function validateUnrelatedProducts()
    {
        if (empty($this->unrelatedProducts)) {
            throw new ServiceException('Document does not have unrelated products left');
        }
    }

    //          Auxiliary          //

    private function shouldSendByEmail(): bool
    {
        if (!defined('CREDIT_NOTE_DOCUMENT_STATUS') || (int)CREDIT_NOTE_DOCUMENT_STATUS === DocumentStatus::DRAFT) {
            return false;
        }

        return defined('CREDIT_NOTE_EMAIL_SEND') && (int)CREDIT_NOTE_EMAIL_SEND === Boolean::YES;
    }
}
