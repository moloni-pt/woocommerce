<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Tools;
use WC_Order;

class OrderCustomer
{
    /**
     * @var WC_Order
     */
    private $order;

    private $customer_id = false;
    private $vat = '999999990';
    private $email = '';
    private $name = 'Cliente';
    private $contactName = '';
    private $zipCode = '1000-100';
    private $address = "Desconhecida";
    private $city = "Desconhecida";
    private $languageId = 1;
    private $countryId = 1;


    /**
     * List of some invalid vat numbers
     * @var array
     */
    private $invalidVats = [
        '999999999',
        '000000000',
        '111111111'
    ];

    /**
     * Documents constructor.
     * @param WC_Order $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * @return bool|int
     * @throws Error
     */
    public function create()
    {
        $this->vat = $this->getVatNumber();
        $this->email = $this->order->get_billing_email();

        $values['name'] = $this->getCustomerName();
        $values['language_id'] = $this->getCustomerLanguageId();
        $values['address'] = $this->getCustomerBillingAddress();
        $values['zip_code'] = $this->getCustomerZip();
        $values['city'] = $this->getCustomerBillingCity();
        $values['country_id'] = $this->getCustomerCountryId();
        $values['email'] = $this->order->get_billing_email();
        $values['phone'] = $this->order->get_billing_phone();
        $values['contact_name'] = $this->contactName;
        $values['maturity_date_id'] = defined('MATURITY_DATE') ? MATURITY_DATE : '';
        $values['payment_method_id'] = defined('PAYMENT_METHOD') ? PAYMENT_METHOD : '';;
        $values['salesman_id'] = "";
        $values['payment_day'] = "";
        $values['discount'] = "";
        $values['credit_limit'] = "";
        $values['delivery_method_id'] = "";
        $values['field_notes'] = "";

        $customerExists = $this->searchForCustomer();
        if (!$customerExists) {
            $values['vat'] = $this->vat;
            $values['number'] = $this->getCustomerNextNumber();
            $result = Curl::simple("customers/insert", $values);
        } else {
            $values['customer_id'] = $customerExists['customer_id'];
            $result = Curl::simple("customers/update", $values);
        }

        if (isset($result['customer_id'])) {
            $this->customer_id = $result['customer_id'];
        } else {
            throw new Error(__("Atenção, houve um erro ao inserir o cliente."));
        }

        return $this->customer_id;
    }

    /**
     * Get the vat number of an order
     * Get it from a custom field and validate if Portuguese
     * @return string
     */
    public function getVatNumber()
    {
        $vat = '999999990';

        if (defined("VAT_FIELD")) {
            $metaVat = trim($this->order->get_meta(VAT_FIELD));
            if (!empty($metaVat)) {
                $vat = $metaVat;
            }
        }

        $billingCountry = $this->order->get_billing_country();

        // Do some more verifications if the vat number is Portuguese
        if ($billingCountry == 'PT') {
            // Remove the PT part from the beginning
            if (strtoupper(substr($vat, 0, 2)) == strtoupper('PT')) {
                $vat = str_ireplace("PT", "", $vat);
            }

            // Check if the vat is one of this
            if (empty($vat) || in_array($vat, $this->invalidVats)) {
                $vat = "999999990";
            }
        }

        $this->vat = $vat;
        return $this->vat;
    }

    /**
     * Checks if the company name is set
     * If the order has a company we issue the document to the company
     * And add the name of the person to the contact name
     * @return string
     */
    public function getCustomerName()
    {
        $billingName = $this->order->get_billing_first_name();
        $billingLastName = $this->order->get_billing_last_name();
        if (!empty($billingLastName)) {
            $billingName .= ' ' . $this->order->get_billing_last_name();
        }

        $billingCompany = trim($this->order->get_billing_company());
        if (!empty($billingCompany)) {
            $this->name = $billingCompany;
            $this->contactName = $billingName;
        } elseif (!empty($billingName)) {
            $this->name = $billingName;
        }


        return $this->name;
    }

    /**
     * Create a customer billing a address
     * @return string
     */
    public function getCustomerBillingAddress()
    {
        $billingAddress = trim($this->order->get_billing_address_1());
        $billingAddress2 = $this->order->get_billing_address_2();
        if (!empty($billingAddress2)) {
            $billingAddress .= " " . trim($billingAddress2);
        }

        if (!empty($billingAddress)) {
            $this->address = $billingAddress;
        }

        return $this->address;
    }

    /**
     * Create a customer billing City
     * @return string
     */
    public function getCustomerBillingCity()
    {
        $billingCity = trim($this->order->get_billing_city());
        if (!empty($billingCity)) {
            $this->city = $billingCity;
        }

        return $this->city;
    }

    /**
     * Gets the zip code of a customer
     * If the customer is Portuguese validate the Vat Number
     * @return string
     */
    public function getCustomerZip()
    {
        $zipCode = $this->order->get_billing_postcode();

        if ($this->order->get_billing_country() == "PT") {
            $zipCode = Tools::zipCheck($zipCode);
        }

        $this->zipCode = $zipCode;
        return $this->zipCode;
    }

    /**
     * Get the customer next available number for incremental inserts
     * @return int
     * @throws Error
     */
    public static function getCustomerNextNumber()
    {
        $results = Curl::simple("customers/getNextNumber", []);
        if (!empty($results['number'])) {
            return ($results['number']);
        } else {
            return (rand(10000000000, 100000000000));
        }
    }

    /**
     * Get the country_id based on a ISO value
     * @return int
     * @throws Error
     */
    public function getCustomerCountryId()
    {
        $countryCode = $this->order->get_billing_country();
        $this->countryId = Tools::getCountryIdFromCode($countryCode);

        return $this->countryId;
    }

    /**
     * If the country of the customer is one of the available we set it to Portuguese
     */
    public function getCustomerLanguageId()
    {
        $this->languageId = in_array($this->countryId, [1]) ? 1 : 2;
        return $this->languageId;
    }

    /**
     * Search for a customer based on $this->vat or $this->email
     * @param string|bool $forField
     * @return bool
     * @throws Error
     */
    public function searchForCustomer($forField = false)
    {
        $result = false;
        $search = [];
        $search['exact'] = 1;
        if ($forField && in_array($forField, ["vat", "email"])) {
            //@todo not important for this plugin
        } else {
            if ($this->vat !== '999999990') {
                $search['vat'] = $this->vat;
                $searchResult = Curl::simple("customers/getByVat", $search);
                if (isset($searchResult[0]['customer_id'])) {
                    $result = $searchResult[0];
                }
            } else if (!empty($this->email)) {
                $search['email'] = $this->email;
                $searchResult = Curl::simple("customers/getByEmail", $search);
                if (isset($searchResult[0]['customer_id'])) {
                    $result = $searchResult[0];
                }
            }
        }

        return $result;
    }
}
