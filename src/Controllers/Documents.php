<?php

namespace Moloni\Controllers;

use Moloni\Exceptions\APIExeption;
use Moloni\Exceptions\DocumentError;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Exceptions\GenericException;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use Moloni\Curl;
use Moloni\Tools;
use Moloni\Storage;
use Moloni\Enums\Boolean;
use Moloni\Enums\DocumentTypes;
use Moloni\Enums\DocumentStatus;

/**
 * Class Documents
 * Used to create or update a Moloni Document
 * @package Moloni\Controllers
 */
class Documents
{
    /**
     * Moloni company data
     *
     * @var array
     */
    private $company;

    /**
     * Document fiscal zone
     *
     * @var string
     */
    private $fiscalZone;

    /**
     * Associated documents
     *
     * @var array
     */
    private $associatedDocuments = [];

    /**
     * WooCommerce order object
     *
     * @var WC_Order
     */
    public $order;

    /**
     * WooCommerce order ID
     *
     * @var int
     */
    private $orderId;

    /**
     * Field used in filter to cancel document creation
     *
     * @var bool
     */
    public $stopProcess = false;

    /**
     * Created Moloni document data
     *
     * @var array
     */
    private $document = [];

    /**
     * Created Moloni document ID
     *
     * @var int
     */
    private $document_id = 0;

    /**
     * Moloni document total
     *
     * @var float
     */
    private $documentTotal = 0;

    /**
     * Moloni document exchage total total
     *
     * @var float
     */
    private $documentExchageTotal = 0;

    /**
     * CAE ID
     *
     * @var int
     */
    private $caeId = 0;

    /**
     * Moloni customer ID
     *
     * @var int
     */
    public $customer_id;

    /**
     * Document set ID
     *
     * @var int
     */
    public $document_set_id;

    /**
     * Document reference
     *
     * @var string
     */
    public $our_reference = '';

    /**
     * Document reference
     *
     * @var string
     */
    public $your_reference = '';

    /**
     * Document data
     *
     * @var string in Y-m-d
     */
    public $date;

    /**
     * Document expiration date
     *
     * @var string in Y-m-d
     */
    public $expiration_date;

    /**
     * Document financial discount
     *
     * @var float
     */
    public $financial_discount = 0;

    /**
     * Document special discount
     *
     * @var float
     */
    public $special_discount = 0;

    /**
     * Document salesman ID
     *
     * @var int
     */
    public $salesman_id = 0;

    /**
     * Document salesman comission
     *
     * @var int
     */
    public $salesman_commission = 0;

    // Delivery parameters being used if the option is set
    public $delivery_datetime;
    public $delivery_method_id = 0;

    public $delivery_departure_address = '';
    public $delivery_departure_city = '';
    public $delivery_departure_zip_code = '';
    public $delivery_departure_country = '';

    public $delivery_destination_address = '';
    public $delivery_destination_city = '';
    public $delivery_destination_country = '';
    public $delivery_destination_zip_code = '';
    public $notes = '';

    public $products = [];

    public $payments = [];

    public $documentType = '';
    public $documentTypeName = '';
    public $documentStatus = 0;

    public $useShipping = 0;
    public $sendEmail = 0;

    /** @var int */
    public $exchange_currency_id;
    public $exchange_rate;

    /**
     * Constructor
     *
     * @param WC_Order $order
     * @param array $company
     *
     * @throws DocumentError
     */
    public function __construct(WC_Order $order, array $company)
    {
        $this->order = $order;
        $this->orderId = $order->get_id();
        $this->company = $company;

        $this->init();
    }

    /**
     * Resets some values after cloning
     *
     * @return void
     */
    public function __clone()
    {
        $this->document = [];
        $this->document_id = 0;

        $this->documentTotal = 0;
        $this->documentExchageTotal = 0;

        $this->associatedDocuments = [];
    }

    /**
     * Associate a document wiht the current one
     *
     * @param int $documentId Document id to associate
     * @param float $value Total value to associate
     * @param array $products Document products
     *
     * @return $this
     */
    public function addAssociatedDocument(int $documentId, float $value, array $products = []): Documents
    {
        $this->associatedDocuments[] = [
            'document_id' => $documentId,
            'value' => $value,
            'products' => $products
        ];

        return $this;
    }

    /**
     * Create Moloni document
     *
     *
     * @return Documents
     *
     * @throws DocumentError
     * @throws DocumentWarning
     */
    public function createDocument(): Documents
    {
        apply_filters('moloni_before_insert_document', $this);

        if ($this->stopProcess) {
            return $this;
        }

        try {
            $insertedDocument = Curl::simple($this->documentType . '/insert', $this->mapPropsToValues());
        } catch (APIExeption $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        if (!isset($insertedDocument['document_id'])) {
            throw new DocumentError(sprintf(__('Atenção, houve um erro ao inserir o documento %s'), $this->order->get_order_number()));
        }

        $this->document_id = $insertedDocument['document_id'];

        $this->saveRecord();

        try {
            $this->document = Curl::simple('documents/getOne', ['document_id' => $insertedDocument['document_id']]);
        } catch (APIExeption $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        $this->documentTotal = (float)$this->document['net_value'];
        $this->documentExchageTotal = (float)$this->document['exchange_total_value'] > 0 ? (float)$this->document['exchange_total_value'] : $this->documentTotal;

        apply_filters('moloni_after_insert_document', $this);

        // If the documents is going to be inserted as closed
        if ($this->shouldCloseDocument()) {
            $this->closeDocument();
        } else {
            $note = __('Documento inserido como rascunho no Moloni');
            $note .= " (" . $this->documentTypeName . ")";

            $this->order->add_order_note($note);
        }

        $this->saveLog();

        return $this;
    }

    /**
     * Close Moloni document
     *
     * @throws DocumentWarning
     */
    public function closeDocument(): void
    {
        // Validate if the document totals match can be closed
        $orderTotal = ((float)$this->order->get_total() - (float)$this->order->get_total_refunded());

        if ($orderTotal !== $this->getDocumentExchageTotal()) {
            $note = __('Documento inserido como rascunho no Moloni');
            $note .= " (" . $this->documentTypeName . ")";

            $this->order->add_order_note($note);

            $viewUrl = esc_url(admin_url('admin.php?page=moloni&action=getInvoice&id=' . $this->document_id));

            throw new DocumentWarning(
                __('O documento foi inserido mas os totais não correspondem.') .
                '<a href="' . $viewUrl . '" target="_BLANK">Ver documento</a>'
            );
        }

        $closeDocument = [
            'document_id' => $this->document_id,
            'status' => DocumentStatus::CLOSED
        ];

        // Associations need to be sent again when closing a document (but can skip product association)
        if (!empty($this->associatedDocuments)) {
            $this->associateDocuments($closeDocument, true);
        }

        // Send email to the client
        if ($this->shouldSendEmail()) {
            $this->order->add_order_note(__('Documento enviado por email para o cliente'));

            $closeDocument['send_email'] = [];
            $closeDocument['send_email'][] = [
                'email' => $this->order->get_billing_email(),
                'name' => $this->document['entity_name'],
                'msg' => ''
            ];
        }

        try {
            Curl::simple($this->documentType . '/update', $closeDocument);
        } catch (APIExeption $e) {
            throw new DocumentWarning($e->getMessage(), $e->getData());
        }

        apply_filters('moloni_after_close_document', $this);

        $note = __('Documento inserido no Moloni');
        $note .= " (" . $this->documentTypeName . ")";

        $this->order->add_order_note($note);
    }

    //          PRIVATES          //

    /**
     * Initialize document values
     *
     * @return void
     *
     * @throws DocumentError
     */
    private function init(): void
    {
        apply_filters('moloni_before_start_document', $this);

        $this
            ->setYourReference()
            ->setDates()
            ->setDocumentStatus()
            ->setCustomer()
            ->setDocumentType()
            ->setDocumentSetId()
            ->setSendEmail()
            ->setFiscalZone()
            ->setProducts()
            ->setShipping()
            ->setFees()
            ->setExchangeRate()
            ->setCae()
            ->setShippingInformation()
            ->setDelivery()
            ->setPaymentMethod()
            ->setNotes();
    }

    /**
     * Save document log
     *
     * @return void
     */
    private function saveLog(): void
    {
        $message = __('{0} foi gerado com sucesso ({1})');
        $message = str_replace('{0}', $this->documentTypeName, $message);
        $message = str_replace('{1}', $this->order->get_order_number(), $message);

        Storage::$LOGGER->info($message, [
            'order_id' => $this->orderId,
            'document_id' => $this->document_id,
            'document_status' => $this->documentStatus,
        ]);
    }

    /**
     * Save document id on order meta
     *
     * @return void
     */
    private function saveRecord(): void
    {
        $this->order->add_meta_data('_moloni_sent', $this->document_id);
        $this->order->save();
    }

    /**
     * Map this object properties to an array to insert/update a moloni document
     *
     * @return array
     */
    private function mapPropsToValues(): array
    {
        $values = [];
        $values['customer_id'] = $this->customer_id;
        $values['document_set_id'] = $this->document_set_id;
        $values['our_reference'] = $this->our_reference;
        $values['your_reference'] = $this->your_reference;
        $values['date'] = $this->date;
        $values['expiration_date'] = $this->expiration_date;
        $values['financial_discount'] = $this->financial_discount;
        $values['special_discount'] = $this->special_discount;
        $values['salesman_id'] = $this->salesman_id;
        $values['salesman_commission'] = $this->salesman_commission;

        $values['notes'] = $this->notes;
        $values['status'] = DocumentStatus::DRAFT;
        $values['eac_id'] = $this->caeId;
        $values['products'] = $this->products;

        if ($this->shouldAddShippingInformation()) {
            $values['delivery_datetime'] = $this->delivery_datetime;
            $values['delivery_method_id'] = $this->delivery_method_id;

            $values['delivery_departure_address'] = $this->delivery_departure_address;
            $values['delivery_departure_city'] = $this->delivery_departure_city;
            $values['delivery_departure_zip_code'] = $this->delivery_departure_zip_code;
            $values['delivery_departure_country'] = $this->delivery_departure_country;

            $values['delivery_destination_address'] = $this->delivery_destination_address;
            $values['delivery_destination_city'] = $this->delivery_destination_city;
            $values['delivery_destination_zip_code'] = $this->delivery_destination_zip_code;
            $values['delivery_destination_country'] = $this->delivery_destination_country;
        }

        if ($this->shouldAddPayment()) {
            $values['payments'] = $this->payments;
        }

        if (!empty($this->exchange_currency_id)) {
            $values['exchange_currency_id'] = $this->exchange_currency_id;
            $values['exchange_rate'] = $this->exchange_rate;
        }

        if (!empty($this->associatedDocuments)) {
            $this->associateDocuments($values);
        }

        return $values;
    }

    //          AUXILIARY          //

    /**
     * Auxiliary method to associate current document to associated list
     *
     * @param array $props API props
     * @param bool|null $skipProducts Skip products association
     *
     * @return void
     */
    private function associateDocuments(array &$props, $skipProducts = false): void
    {
        // If multiple documents are associated, the need a global product counter
        // Starts in -1 because the first thing we do is to increment its value
        $currentProductIndex = -1;

        $props['associated_documents'] = [];

        foreach ($this->associatedDocuments as $associatedDocument) {
            $newAssociation = [
                'associated_id' => $associatedDocument['document_id'],
                'value' => $associatedDocument['value']
            ];

            $props['associated_documents'][] = $newAssociation;

            // Skip document product association
            if ($skipProducts) {
                continue;
            }

            if (!empty($associatedDocument['products'])) {
                // Associate products from both documents
                // We assume that the order of the documents is the same (beware if tring to do custom stuff)
                foreach ($associatedDocument['products'] as $associatedProduct) {
                    $currentProductIndex++;

                    // To avoid errors, check lenght
                    if (!isset($props['products'][$currentProductIndex])) {
                        continue;
                    }

                    // Ids have to match
                    if ((int)$props['products'][$currentProductIndex]['product_id'] !== (int)$associatedProduct['product_id']) {
                        continue;
                    }

                    // Both have to be simple or bundle product
                    if (empty($associatedProduct['child_products']) !== empty($props['products'][$currentProductIndex]['child_products'])) {
                        continue;
                    }

                    if (empty($associatedProduct['child_products'])) {
                        $props['products'][$currentProductIndex]['origin_id'] = (int)$associatedDocument['document_id'];
                        $props['products'][$currentProductIndex]['related_id'] = (int)$associatedProduct['document_product_id'];
                    } else {
                        foreach ($associatedProduct['child_products'] as $childIndex => $childProduct) {
                            // To avoid errors, check lenght
                            if (!isset($props['products'][$currentProductIndex]['child_products'][$childIndex])) {
                                continue;
                            }

                            // Ids have to match
                            if ((int)$props['products'][$currentProductIndex]['child_products'][$childIndex]['product_id'] !== (int)$childProduct['product_id']) {
                                continue;
                            }

                            $props['products'][$currentProductIndex]['child_products'][$childIndex]['origin_id'] = (int)$associatedDocument['document_id'];
                            $props['products'][$currentProductIndex]['child_products'][$childIndex]['related_id'] = (int)$childProduct['document_product_id'];
                        }
                    }
                }
            }
        }
    }

    //          GETS          //

    /**
     * Get document id
     *
     * @return int
     */
    public function getDocumentId(): int
    {
        return $this->document_id;
    }

    /**
     * Get document total
     *
     * @return float|int
     */
    public function getDocumentTotal()
    {
        return $this->documentTotal;
    }

    /**
     * Get created document products
     *
     * @return array
     */
    public function getDocumentProducts(): array
    {
        return $this->document['products'] ?? [];
    }

    /**
     * Get document exchange total
     *
     * @return float|int
     */
    public function getDocumentExchageTotal()
    {
        return $this->documentExchageTotal;
    }

    //          SETS          //

    /**
     * Set document status
     *
     * @param $documentStatus
     *
     * @return $this
     */
    public function setDocumentStatus($documentStatus = null): Documents
    {
        switch (true) {
            case $documentStatus !== null:
                $this->documentStatus = (int)$documentStatus;

                break;
            case defined('DOCUMENT_STATUS'):
                $this->documentStatus = (int)DOCUMENT_STATUS;

                break;
            default:
                $this->documentStatus = DocumentStatus::DRAFT;

                break;
        }

        return $this;
    }

    /**
     * Set document type
     *
     * @param null $documentType
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setDocumentType($documentType = null): Documents
    {
        switch (true) {
            case !empty($documentType):
                $this->documentType = $documentType;

                break;
            case defined('DOCUMENT_TYPE'):
                $this->documentType = DOCUMENT_TYPE;
                break;
            default:
                $this->documentType = '';

                break;
        }

        if (empty($this->documentType)) {
            throw new DocumentError(__('Tipo de documento não definido nas opções'));
        }

        $this->documentTypeName = DocumentTypes::getDocumentTypeName($this->documentType);

        return $this;
    }

    /**
     * Set send by email
     *
     * @param $sendByEmail
     *
     * @return $this
     */
    public function setSendEmail($sendByEmail = null): Documents
    {
        switch (true) {
            case $sendByEmail !== null:
                $this->sendEmail = (int)$sendByEmail;

                break;
            case defined('EMAIL_SEND'):
                $this->sendEmail = (int)EMAIL_SEND;

                break;
            default:
                $this->sendEmail = 0;

                break;
        }

        return $this;
    }

    /**
     * Set use CAE ID
     *
     * @return $this
     */
    public function setCae(): Documents
    {
        if (defined('DOCUMENT_SET_CAE_ID')) {
            $this->caeId = (int)DOCUMENT_SET_CAE_ID;
        } else {
            $this->caeId = 0;
        }

        return $this;
    }

    /**
     * Set use shipping information
     *
     * @return $this
     */
    public function setShippingInformation(): Documents
    {
        if (defined('SHIPPING_INFO')) {
            $this->useShipping = (int)SHIPPING_INFO;
        } else {
            $this->useShipping = 0;
        }

        return $this;
    }

    /**
     * Set document reference
     *
     * @return $this
     */
    public function setYourReference(): Documents
    {
        $this->your_reference = '#' . $this->order->get_order_number();

        return $this;
    }

    /**
     * Set dates
     *
     * @return $this
     */
    public function setDates(): Documents
    {
        $this->date = date('Y-m-d');
        $this->expiration_date = date('Y-m-d');

        return $this;
    }

    /**
     * Set costumer
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setCustomer(): Documents
    {
        try {
            $this->customer_id = (new OrderCustomer($this->order))->create();
        } catch (APIExeption|GenericException $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        return $this;
    }

    /**
     * Set document set id
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setDocumentSetId(): Documents
    {
        if (!defined('DOCUMENT_SET_ID') || (int)DOCUMENT_SET_ID === 0) {
            throw new DocumentError(__('Série de documentos em falta. <br>Por favor selecione uma série nas opções do plugin'));
        }

        $this->document_set_id = DOCUMENT_SET_ID;

        return $this;
    }

    /**
     * Set fiscal zone
     *
     * @return $this
     */
    public function setFiscalZone(): Documents
    {
        $fiscalZone = null;

        switch (get_option('woocommerce_tax_based_on')) {
            case 'billing':
                $fiscalZone = $this->order->get_billing_country();

                break;
            case 'shipping':
                $fiscalZone = $this->order->get_shipping_country();

                break;
            case 'base':
                $fiscalZone = $this->company['country']['iso_3166_1'];

                break;
        }

        if (empty($fiscalZone)) {
            $fiscalZone = $this->company['country']['iso_3166_1'];
        }

        $this->fiscalZone = strtoupper($fiscalZone);

        return $this;
    }

    /**
     * Set products
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setProducts(): Documents
    {
        foreach ($this->order->get_items() as $orderProduct) {
            /** Skip "child" products created by "YITH WooCommerce Product Bundles" plugin */
            if ($orderProduct->get_meta('_bundled_by')) {
                continue;
            }

            /**
             * @var $orderProduct WC_Order_Item_Product
             */
            $newOrderProduct = new OrderProduct($orderProduct, $this->order, count($this->products), $this->fiscalZone);

            try {
                $newOrderProduct->create();
            } catch (APIExeption|GenericException $e) {
                throw new DocumentError($e->getMessage(), $e->getData());
            }

            if ($newOrderProduct->qty > 0) {
                $this->products[] = $newOrderProduct->mapPropsToValues();
            }
        }

        return $this;
    }

    /**
     * Set shipping information
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setShipping(): Documents
    {
        if ($this->order->get_shipping_method() && (float)$this->order->get_shipping_total() > 0) {
            $newOrderShipping = new OrderShipping($this->order, count($this->products), $this->fiscalZone);

            try {
                $newOrderShipping->create();
            } catch (APIExeption|GenericException $e) {
                throw new DocumentError($e->getMessage(), $e->getData());
            }

            if ($newOrderShipping->getPrice() > 0) {
                $this->products[] = $newOrderShipping->mapPropsToValues();
            }
        }

        return $this;
    }

    /**
     * Set fees
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setFees(): Documents
    {
        foreach ($this->order->get_fees() as $key => $item) {
            /** @var $item WC_Order_Item_Fee */
            $feePrice = abs($item['line_total']);

            if ($feePrice > 0) {
                $newOrderFee = new OrderFees($item, count($this->products), $this->fiscalZone);

                try {
                    $this->products[] = $newOrderFee->create()->mapPropsToValues();
                } catch (APIExeption|GenericException $e) {
                    throw new DocumentError($e->getMessage(), $e->getData());
                }
            }
        }

        return $this;
    }

    /**
     * Set exchage rate
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setExchangeRate(): Documents
    {
        if ($this->company['currency']['iso4217'] !== $this->order->get_currency()) {

            try {
                $this->exchange_currency_id = Tools::getCurrencyIdFromCode($this->order->get_currency());
                $this->exchange_rate = Tools::getCurrencyExchangeRate($this->company['currency']['currency_id'], $this->exchange_currency_id);
            } catch (APIExeption $e) {
                throw new DocumentError($e->getMessage(), $e->getData());
            }

            if (!empty($this->products) && is_array($this->products)) {
                foreach ($this->products as &$product) {
                    $product['price'] /= $this->exchange_rate;

                    if (!empty($product['child_products'])) {
                        foreach ($product['child_products'] as &$child_product) {
                            $child_product['price'] /= $this->exchange_rate;
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Set the document Payment Method
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setPaymentMethod(): Documents
    {
        $paymentMethodName = $this->order->get_payment_method_title();

        if (!empty($paymentMethodName)) {
            $paymentMethod = new Payment($paymentMethodName);

            try {
                if (!$paymentMethod->loadByName()) {
                    $paymentMethod->create();
                }
            } catch (APIExeption|GenericException $e) {
                throw new DocumentError($e->getMessage(), $e->getData());
            }

            if ((int)$paymentMethod->payment_method_id > 0) {
                $orderTotal = (float)$this->order->get_total() - (float)$this->order->get_total_refunded();

                //Use exchange rate value on payment method value
                if ($this->exchange_rate && $this->exchange_rate > 0) {
                    $orderTotal /= $this->exchange_rate;
                }

                $this->payments[] = [
                    'payment_method_id' => (int)$paymentMethod->payment_method_id,
                    'date' => date('Y-m-d H:i:s'),
                    'value' => $orderTotal
                ];
            }
        }

        return $this;
    }

    /**
     * Set the document customer notes
     *
     * @return $this
     */
    public function setNotes(): Documents
    {
        if (defined('ADD_ORDER_NOTES') && (int)ADD_ORDER_NOTES === Boolean::NO) {
            return $this;
        }

        $notes = $this->order->get_customer_order_notes();

        if (!empty($notes)) {
            foreach ($notes as $index => $note) {
                $this->notes .= $note->comment_content;
                if ($index !== count($notes) - 1) {
                    $this->notes .= '<br>';
                }
            }
        }

        return $this;
    }

    /**
     * Set delivery details
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setDelivery(): Documents
    {
        $shippingName = $this->order->get_shipping_method();

        if (empty($shippingName)) {
            return $this;
        }

        $this->delivery_destination_zip_code = $this->order->get_shipping_postcode();

        if ($this->order->get_shipping_country() === 'PT') {
            $this->delivery_destination_zip_code = Tools::zipCheck($this->delivery_destination_zip_code);
        }

        $deliveryMethod = new DeliveryMethod($this->order->get_shipping_method());

        try {
            if (!$deliveryMethod->loadByName()) {
                $deliveryMethod->create();
            }
        } catch (APIExeption|GenericException $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        $this->delivery_method_id = $deliveryMethod->delivery_method_id > 0 ?
            $deliveryMethod->delivery_method_id : $this->company['delivery_method_id'];

        $this->delivery_datetime = date('Y-m-d H:i:s');

        $loadSetting = defined('LOAD_ADDRESS') ? (int)LOAD_ADDRESS : 0;

        if ($loadSetting === 1 &&
            defined('LOAD_ADDRESS_CUSTOM_ADDRESS') &&
            defined('LOAD_ADDRESS_CUSTOM_CITY') &&
            defined('LOAD_ADDRESS_CUSTOM_CODE') &&
            defined('LOAD_ADDRESS_CUSTOM_COUNTRY')) {
            $this->delivery_departure_address = LOAD_ADDRESS_CUSTOM_ADDRESS;
            $this->delivery_departure_city = LOAD_ADDRESS_CUSTOM_CITY;
            $this->delivery_departure_zip_code = LOAD_ADDRESS_CUSTOM_CODE;
            $this->delivery_departure_country = (int)LOAD_ADDRESS_CUSTOM_COUNTRY;
        } else {
            $this->delivery_departure_address = $this->company['address'];
            $this->delivery_departure_city = $this->company['city'];
            $this->delivery_departure_zip_code = $this->company['zip_code'];
            $this->delivery_departure_country = $this->company['country_id'];
        }

        $this->delivery_destination_address = $this->order->get_shipping_address_1() . ' ' . $this->order->get_shipping_address_2();
        $this->delivery_destination_city = $this->order->get_shipping_city();

        try {
            $this->delivery_destination_country = Tools::getCountryIdFromCode($this->order->get_shipping_country());
        } catch (APIExeption $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        return $this;
    }

    //          VERIFICATIONS          //

    /**
     * Checks if document should have payments
     *
     * @return bool
     */
    protected function shouldAddPayment(): bool
    {
        return DocumentTypes::hasPayments($this->documentType);
    }

    /**
     * Checks if document should be closed
     *
     * @return bool
     */
    protected function shouldCloseDocument(): bool
    {
        return $this->documentStatus === DocumentStatus::CLOSED;
    }

    /**
     * Checks if document should be sent via email
     *
     * @return bool
     */
    protected function shouldSendEmail(): bool
    {
        return $this->sendEmail === Boolean::YES;
    }

    /**
     * Checks if document should have shipping information
     *
     * @return bool
     */
    protected function shouldAddShippingInformation(): bool
    {
        if (DocumentTypes::requiresDelivery($this->documentType)) {
            return true;
        }

        return $this->useShipping === Boolean::YES;
    }
}
