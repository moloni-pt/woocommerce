<?php

namespace Moloni\Controllers;

use WC_Order;
use Moloni\Curl;
use Moloni\Tools;
use Moloni\Exceptions\APIException;
use Moloni\Exceptions\GenericException;

class OrderCustomer
{
    /**
     * @var WC_Order
     */
    private $order;

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
     * @return mixed
     *
     * @throws APIException
     * @throws GenericException
     */
    public function create($retry = 0)
    {
        $nameData = $this->getCustomerName();

        $values = [
            'vat' => $this->getVatNumber(),
            'name' => $nameData['name'],
            'contact_name' => $nameData['contact_name'],
            'address' => $this->getCustomerBillingAddress(),
            'zip_code' => $this->getCustomerZip(),
            'city' => $this->getCustomerBillingCity(),
            'country_id' => $this->getCustomerCountryId(),
            'email' => $this->order->get_billing_email(),
            'phone' => $this->order->get_billing_phone(),
            'maturity_date_id' => defined('MATURITY_DATE') ? MATURITY_DATE : '',
            'payment_method_id' => defined('PAYMENT_METHOD') ? PAYMENT_METHOD : '',
            'salesman_id' => '',
            'payment_day' => '',
            'discount' => '',
            'credit_limit' => '',
            'delivery_method_id' => '',
            'field_notes' => '',
        ];

        $values['language_id'] = $this->getCustomerLanguageId($values['country_id']);

        $values = apply_filters('moloni_before_search_customer', $values);

        if (!empty($values['customer_id'])) {
            return (int)$values['customer_id'];
        }

        $customerExists = $this->searchForCustomer($values);

        if (!$customerExists) {
            $values['number'] = $this->nextNumberCreator();

            $result = Curl::simple('customers/insert', $values);
        } else {
            $values['customer_id'] = $customerExists['customer_id'];
            $result = Curl::simple('customers/update', $values);
        }

        if (!isset($result['customer_id'])) {
            if ($retry < 3 && !$customerExists && $this->shouldRetryInsert($result)) {
                return $this->create($retry + 1);
            }

            throw new GenericException(__('Atenção, houve um erro ao inserir o cliente.'));
        }

        return (int)$result['customer_id'];
    }

    /**
     * Get the vat number of an order
     * Get it from a custom field and validate if Portuguese
     * @return string
     */
    public function getVatNumber(): string
    {
        $vat = '999999990';

        if (defined('VAT_FIELD')) {
            $metaVat = trim($this->order->get_meta(VAT_FIELD));
            if (!empty($metaVat)) {
                $vat = $metaVat;
            }
        }

        $billingCountry = $this->order->get_billing_country();

        // Do some more verifications if the vat number is Portuguese
        if ($billingCountry === 'PT') {
            // Remove the PT part from the beginning
            if (stripos($vat, strtoupper('PT')) === 0) {
                $vat = str_ireplace('PT', '', $vat);
            }

            // Check if the vat is one of this
            if (empty($vat) || in_array($vat, $this->invalidVats, false)) {
                $vat = '999999990';
            }
        }

        return $vat;
    }

    /**
     * Checks if the company name is set
     * If the order has a company we issue the document to the company
     * And add the name of the person to the contact name
     *
     * @return array
     */
    public function getCustomerName(): array
    {
        $billingName = trim($this->order->get_billing_first_name() ?? '');
        $billingLastName = trim($this->order->get_billing_last_name() ?? '');
        $billingCompany = trim($this->order->get_billing_company() ?? '');

        if (!empty($billingLastName)) {
            $billingName .= ' ' . $billingLastName;
        }

        $name = 'Cliente';
        $contactName = '';

        if (!empty($billingCompany)) {
            $name = $billingCompany;
            $contactName = $billingName;
        } elseif (!empty($billingName)) {
            $name = $billingName;
        }

        return ['name' => $name, 'contact_name' => $contactName];
    }

    /**
     * Create a customer billing a address
     * @return string
     */
    public function getCustomerBillingAddress(): string
    {
        $billingAddress = trim($this->order->get_billing_address_1());
        $billingAddress2 = $this->order->get_billing_address_2();

        if (!empty($billingAddress2)) {
            $billingAddress .= ' ' . trim($billingAddress2);
        }

        return empty($billingAddress) ? 'Desconhecida' : $billingAddress;
    }

    /**
     * Create a customer billing City
     * @return string
     */
    public function getCustomerBillingCity(): string
    {
        $city = $this->order->get_billing_city();

        return empty($city) ? 'Desconhecida' : $city;
    }

    /**
     * Gets the zip code of a customer
     * If the customer is Portuguese validate the Vat Number
     * @return string
     */
    public function getCustomerZip(): string
    {
        $zipCode = $this->order->get_billing_postcode();

        if ($this->order->get_billing_country() === 'PT') {
            $zipCode = Tools::zipCheck($zipCode);
        }

        return empty($zipCode ?? '') ? '1000-100' : $zipCode;
    }

    /**
     * Get the customer next available number for incremental inserts
     *
     * @throws APIException
     */
    public static function getCustomerNextNumber()
    {
        $results = Curl::simple('customers/getNextNumber', []);

        if (!empty($results['number'])) {
            return ($results['number']);
        }

        return (mt_rand(10000000000, 100000000000));
    }

    /**
     * Get the country_id based on a ISO value
     *
     * @throws APIException
     */
    public function getCustomerCountryId(): string
    {
        $countryCode = $this->order->get_billing_country();

        return Tools::getCountryIdFromCode($countryCode);
    }

    /**
     * If the country of the customer is one of the available we set it to Portuguese
     */
    public function getCustomerLanguageId($countryId): int
    {
        return (int)$countryId === 1 ? 1 : 2;
    }

    /**
     * Search for a customer based on vat or email
     *
     * @param array $values
     *
     * @return bool|array
     *
     * @throws APIException
     */
    public function searchForCustomer(array $values = [])
    {
        $search = [
            'exact' => 1,
        ];

        $values['vat'] = $values['vat'] ?? '';

        if ($values['vat'] !== '999999990') {
            $search['vat'] = $values['vat'];

            $searchResult = Curl::simple('customers/getByVat', $search);

            if (empty($searchResult) || !is_array($searchResult)) {
                return false;
            }

            foreach ($searchResult as $customer) {
                if (!isset($customer['customer_id'])) {
                    continue;
                }

                if ((string)$customer['vat'] !== (string)$values['vat']) {
                    continue;
                }

                return $customer;
            }
        } elseif (!empty($values['email'])) {
            $search['email'] = $values['email'];

            $searchResult = Curl::simple('customers/getByEmail', $search);

            if (empty($searchResult) || !is_array($searchResult)) {
                return false;
            }

            foreach ($searchResult as $customer) {
                if (!isset($customer['customer_id']) || !isset($customer['vat'])) {
                    continue;
                }

                if ($customer['vat'] !== '999999990') {
                    continue;
                }

                return $customer;
            }
        }

        return false;
    }

    //                 Auxiliary                 //

    private function nextNumberCreator()
    {
        // Normal way

        if (!defined('CUSTOMER_NUMBER_PREFIX') || empty(CUSTOMER_NUMBER_PREFIX)) {
            $results = Curl::simple('customers/getNextNumber', []);

            if (!empty($results['number'])) {
                return ($results['number']);
            }

            return (mt_rand(10000000000, 100000000000));
        }

        // Find by prefix

        $results = Curl::simple('customers/getByNumber', [
            'number' => CUSTOMER_NUMBER_PREFIX . "%",
            'order_by_field' => 'customer_id',
            'order_by_ordering' => 'desc',
            'qty' => 1,
            'exact' => 1,
        ]);

        if (!isset($results[0]['number'])) {
            return CUSTOMER_NUMBER_PREFIX . '1';
        }

        $number = substr($results[0]['number'], strlen(CUSTOMER_NUMBER_PREFIX));

        return CUSTOMER_NUMBER_PREFIX . ((int)$number + 1);
    }

    private function shouldRetryInsert($result): bool
    {
        if (empty($result) || !is_array($result)) {
            return false;
        }

        foreach ($result as $error) {
            if (!isset($error["code"])) {
                continue;
            }

            if ($error["code"] === '4 number') {
                return true;
            }
        }

        return false;
    }
}
