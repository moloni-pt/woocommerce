<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Tools;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;

/**
 * Class Documents
 * Used to create or update a Moloni Document
 * @package Moloni\Controllers
 */
class Documents
{
    /** @var array */
    private $company = [];

    /** @var int */
    private $orderId;

    /** @var WC_Order */
    public $order;

    /** @var bool|Error */
    private $error = false;

    /**
     * Field used in filter to cancel document creation
     *
     * @var bool
     */
    public $stopProcess = false;

    /** @var int */
    public $document_id;

    /** @var int */
    public $customer_id;

    /** @var int */
    public $document_set_id;

    /** @var int */
    public $documentId;

    /** @var string */
    public $our_reference = '';

    /** @var string */
    public $your_reference = '';

    /** @var string in Y-m-d */
    public $date;

    /** @var string in Y-m-d */
    public $expiration_date;

    /** @var float */
    public $financial_discount = 0;

    /** @var float */
    public $special_discount = 0;

    /** @var int */
    public $salesman_id = 0;

    /** @var int */
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

    public $status = 0;

    public $products = [];
    public $payments = [];

    public $documentType;

    /** @var int */
    public $exchange_currency_id;
    public $exchange_rate;

    /**
     * Documents constructor.
     * @param int $orderId
     * @throws Error
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
        $this->order = new WC_Order((int)$orderId);

        if (!defined('DOCUMENT_TYPE')) {
            throw new Error(__('Tipo de documento não definido nas opções'));
        }

        $this->documentType = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : DOCUMENT_TYPE;
    }

    /**
     * Gets the error object
     * @return bool|Error
     */
    public function getError()
    {
        return $this->error ?: false;
    }

    /**
     * @return mixed
     * @throws Error
     */
    public function getDocumentId()
    {
        if ($this->documentId > 0) {
            return $this->documentId;
        }

        throw new Error(__('Document not found'));
    }

    /**
     * @return Documents
     */
    public function createDocument()
    {
        try {
            apply_filters('moloni_before_start_document', $this);

            if ($this->stopProcess) {
                return $this;
            }

            $this->customer_id = (new OrderCustomer($this->order))->create();
            $this->document_set_id = $this->getDocumentSetId();

            $this->date = date('Y-m-d');
            $this->expiration_date = date('Y-m-d');

            $this->your_reference = '#' . $this->order->get_order_number();

            $this
                ->setProducts()
                ->setShipping()
                ->setFees()
                ->setExchangeRate()
                ->setShippingInfo()
                ->setPaymentMethod()
                ->setNotes();

            // One last validation
            if ((!isset($_GET['force']) || sanitize_text_field($_GET['force']) !== 'true') && $this->isReferencedInDatabase()) {
                $forceUrl = 'admin.php?page=moloni&action=genInvoice&id=' . $this->orderId . '&force=true';

                if (isset($_GET['document_type'])) {
                    $forceUrl .= '&document_type=' . sanitize_text_field($_GET['document_type']);
                }

                throw new Error(
                    __('O documento da encomenda ' . $this->order->get_order_number() . ' já foi gerado anteriormente!') .
                    " <a href='$forceUrl'>" . __('Gerar novamente') . '</a>'
                );
            }

            apply_filters('moloni_before_insert_document', $this);

            if ($this->stopProcess) {
                return $this;
            }

            $insertedDocument = Curl::simple($this->documentType . '/insert', $this->mapPropsToValues());

            if (!isset($insertedDocument['document_id'])) {
                throw new Error(sprintf(__('Atenção, houve um erro ao inserir o documento %s'), $this->order->get_order_number()));
            }

            $this->document_id = $insertedDocument['document_id'];

            add_post_meta($this->orderId, '_moloni_sent', $this->document_id, true);

            $addedDocument = Curl::simple('documents/getOne', ['document_id' => $insertedDocument['document_id']]);

            apply_filters('moloni_after_insert_document', $this);

            // If the documents is going to be inserted as closed
            if (defined('DOCUMENT_STATUS') && DOCUMENT_STATUS) {

                // Validate if the document totals match can be closed
                $orderTotal = ((float)$this->order->get_total() - (float)$this->order->get_total_refunded());
                $documentTotal = (float)$addedDocument['exchange_total_value'] > 0 ? (float)$addedDocument['exchange_total_value'] : (float)$addedDocument['net_value'];

                if ($orderTotal !== $documentTotal) {
                    $viewUrl = admin_url('admin.php?page=moloni&action=getInvoice&id=' . $this->document_id);
                    throw new Error(
                        __('O documento foi inserido mas os totais não correspondem. ') .
                        '<a href="' . $viewUrl . '" target="_BLANK">Ver documento</a>'
                    );
                }

                $closeDocument = [];
                $closeDocument['document_id'] = $this->document_id;
                $closeDocument['status'] = 1;

                // Send email to the client
                if (defined('EMAIL_SEND') && EMAIL_SEND) {
                    $this->order->add_order_note(__('Documento enviado por email para o cliente'));

                    $closeDocument['send_email'] = [];
                    $closeDocument['send_email'][] = [
                        'email' => $this->order->get_billing_email(),
                        'name' => $addedDocument['entity_name'],
                        'msg' => ''
                    ];
                }

                Curl::simple($this->documentType . '/update', $closeDocument);

                apply_filters('moloni_after_close_document', $this);

                $this->order->add_order_note(__('Documento inserido no Moloni'));
            } else {
                $this->order->add_order_note(__('Documento inserido como rascunho no Moloni'));
            }
        } catch (Error $error) {
            $this->document_id = 0;
            $this->error = $error;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setProducts()
    {
        foreach ($this->order->get_items() as $itemIndex => $orderProduct) {
            /** @var $orderProduct WC_Order_Item_Product */
            $newOrderProduct = new OrderProduct($orderProduct, $this->order, count($this->products));
            $this->products[] = $newOrderProduct->create()->mapPropsToValues();

        }

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setShipping()
    {
        if ($this->order->get_shipping_method() && (float)$this->order->get_shipping_total() > 0) {
            $newOrderShipping = new OrderShipping($this->order, count($this->products));
            $this->products[] = $newOrderShipping->create()->mapPropsToValues();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setFees()
    {
        foreach ($this->order->get_fees() as $key => $item) {
            /** @var $item WC_Order_Item_Fee */
            $feePrice = abs($item['line_total']);

            if ($feePrice > 0) {
                $newOrderFee = new OrderFees($item, count($this->products));
                $this->products[] = $newOrderFee->create()->mapPropsToValues();

            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setExchangeRate()
    {

        $company = Curl::simple('companies/getOne', []);
        if ($company['currency']['iso4217'] !== $this->order->get_currency()) {
            $this->exchange_currency_id = Tools::getCurrencyIdFromCode($this->order->get_currency());
            $this->exchange_rate = Tools::getCurrencyExchangeRate($company['currency']['currency_id'], $this->exchange_currency_id);

            if (!empty($this->products) && is_array($this->products)) {
                foreach ($this->products as &$product) {
                    $product['price'] /= $this->exchange_rate;
                }
            }
        }

        return $this;
    }

    /**
     * Set the document Payment Method
     * @return $this
     * @throws Error
     */
    private function setPaymentMethod()
    {
        $paymentMethodName = $this->order->get_payment_method_title();

        if (!empty($paymentMethodName)) {
            $paymentMethod = new Payment($paymentMethodName);
            if (!$paymentMethod->loadByName()) {
                $paymentMethod->create();
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
     */
    private function setNotes()
    {
        $notes = $this->order->get_customer_order_notes();
        if (!empty($notes)) {
            foreach ($notes as $index => $note) {
                $this->notes .= $note->comment_content;
                if ($index !== count($notes) - 1) {
                    $this->notes .= '<br>';
                }
            }
        }
    }

    /**
     * @return $this
     * @throws Error
     */
    public function setShippingInfo()
    {
        if (defined('SHIPPING_INFO') && SHIPPING_INFO) {
            $shippingName = $this->order->get_shipping_method();

            if (empty($shippingName)) {
                return $this;
            }

            $this->company = Curl::simple('companies/getOne', []);
            $this->delivery_destination_zip_code = $this->order->get_shipping_postcode();
            if ($this->order->get_shipping_country() === 'PT') {
                $this->delivery_destination_zip_code = Tools::zipCheck($this->delivery_destination_zip_code);
            }

            $deliveryMethod = new DeliveryMethod($this->order->get_shipping_method());
            if (!$deliveryMethod->loadByName()) {
                $deliveryMethod->create();
            }

            $this->delivery_method_id = $deliveryMethod->delivery_method_id > 0 ?
                $deliveryMethod->delivery_method_id : $this->company['delivery_method_id'];

            $this->delivery_datetime = date('Y-m-d H:i:s');

            $this->delivery_departure_address = $this->company['address'];
            $this->delivery_departure_city = $this->company['city'];
            $this->delivery_departure_zip_code = $this->company['zip_code'];
            $this->delivery_departure_country = $this->company['country_id'];

            $this->delivery_destination_address = $this->order->get_shipping_address_1() . ' ' . $this->order->get_shipping_address_2();
            $this->delivery_destination_city = $this->order->get_shipping_city();
            $this->delivery_destination_country = Tools::getCountryIdFromCode($this->order->get_shipping_country());
        }


        return $this;
    }

    /**
     * @return int
     * @throws Error
     */
    public function getDocumentSetId()
    {
        if (defined('DOCUMENT_SET_ID') && (int)DOCUMENT_SET_ID > 0) {
            return DOCUMENT_SET_ID;
        }

        throw new Error(__('Série de documentos em falta. <br>Por favor seleccione uma série nas opções do plugin', false));
    }

    /**
     * Checks if this document is referenced in database
     * @return bool
     */
    public function isReferencedInDatabase()
    {
        return $this->order->get_meta('_moloni_sent') ? true : false;
    }

    /**
     * Map this object properties to an array to insert/update a moloni document
     * @return array
     */
    private function mapPropsToValues()
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
        $values['status'] = $this->status;

        if ((int)$this->delivery_method_id > 0) {
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

        $values['payments'] = $this->payments;

        $values['products'] = $this->products;

        if (!empty($this->exchange_currency_id)) {
            $values['exchange_currency_id'] = $this->exchange_currency_id;
            $values['exchange_rate'] = $this->exchange_rate;
        }

        return $values;
    }

    /**
     * This method will download a document if it is closed
     * Or it will redirect to the Moloni edit page
     * @param $documentId
     * @return bool
     * @throws Error
     */
    public static function showDocument($documentId)
    {
        $values['document_id'] = $documentId;
        $invoice = Curl::simple('documents/getOne', $values);

        if (!isset($invoice['document_id'])) {
            return false;
        }

        if ((int)$invoice['status'] === 1) {
            $url = Curl::simple('documents/getPDFLink', $values);
            header('Location: ' . $url['url']);
        } else {
            if (defined('COMPANY_SLUG')) {
                $slug = COMPANY_SLUG;
            } else {
                $meInfo = Curl::simple('companies/getOne', []);
                $slug = $meInfo['slug'];
            }


            header('Location: https://moloni.pt/' . $slug . '/' . self::getDocumentTypeName($invoice) . '/showDetail/' . $invoice['document_id']);
        }
        exit;
    }

    /**
     * @param int $documentId
     * @throws Error
     * @deprecated In favor of API sending methods
     */
    private function sendOldEmail($documentId)
    {
        $invoice = Curl::simple('documents/getOne', ['document_id' => $documentId]);

        $meInfo = Curl::simple('companies/getOne', []);
        $email = $this->order->get_billing_email();
        $subject = 'Envio de documento | ' . self::getDocumentTypeName($invoice) . $invoice['document_set']['name'] . '-' . $invoice['number'] . ' | ' . date('Y-m-d');

        $date = explode('T', $invoice['date']);
        $date = $date[0];

        $url = 'http://plugins.moloni.com/templates/emails/invoice.txt';

        $response = wp_remote_get($url);
        $message = wp_remote_retrieve_body($response);

        $pdfURL = Curl::simple('documents/getPDFLink', ['document_id' => $invoice['document_id']]);

        $message = str_replace(
            [
                '{{image}}',
                '{{nome_empresa}}',
                '{{data_hoje}}',
                '{{nome_cliente}}',
                '{{documento_tipo}}',
                '{{documento_numero}}',
                '{{documento_emissao}}',
                '{{documento_vencimento}}',
                '{{documento_total}}',
                '{{documento_url}}',
                '{{empresa_nome}}',
                '{{empresa_morada}}',
                '{{empresa_email}}'
            ], [
            $meInfo['image'],
            $meInfo['name'],
            date('Y-m-d'),
            $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(),
            self::getDocumentTypeName($invoice), $invoice['document_set']['name'] . '-' . $invoice['number'],
            $date,
            $date,
            $invoice['net_value'] . '€',
            $pdfURL['url'],
            $meInfo['name'],
            $meInfo['address'],
            $meInfo['mails_sender_address']
        ], $message
        );

        $headers = [
            'Reply-To' => $meInfo['mails_sender_name'] . ' <' . $meInfo['mails_sender_address'] . '>'
        ];

        wp_mail($email, $subject, $message, $headers);
    }

    private static function getDocumentTypeName($invoice)
    {
        switch ($invoice['document_type']['saft_code']) {
            case 'FT' :
            default:
                $typeName = 'Faturas';
                break;
            case 'FR' :
                $typeName = 'FaturasRecibo';
                break;
            case 'FS' :
                $typeName = 'FaturaSimplificada';
                break;
            case 'PF' :
                $typeName = 'FaturasProForma';
                break;
            case 'GT' :
                $typeName = 'GuiasTransporte';
                break;
            case 'NEF' :
                $typeName = 'NotasEncomenda';
                break;
            case 'OR':
                $typeName = 'Orcamentos';
                break;
        }

        return $typeName;
    }
}