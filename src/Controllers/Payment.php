<?php
/**
 *
 *   Plugin Name:  Moloni
 *   Plugin URI:   https://plugins.moloni.com/woocommerce
 *   Description:  Send your orders automatically to your Moloni invoice software
 *   Version:      0.0.1
 *   Author:       Moloni.pt
 *   Author URI:   https://moloni.pt
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace Moloni\Controllers;


use Moloni\Curl;
use Moloni\Exceptions\APIExeption;
use Moloni\Exceptions\GenericException;

class Payment
{
    public $payment_method_id;
    public $name;
    public $value = 0;

    /**
     * Payment constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = trim($name);
    }

    /**
     * This method SHOULD be replaced by a productCategories/getBySearch
     *
     * @throws APIExeption
     */
    public function loadByName()
    {
        $paymentMethods = Curl::simple('paymentMethods/getAll', []);

        if (!empty($paymentMethods) && is_array($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod['name'] === $this->name) {
                    $this->payment_method_id = $paymentMethod['payment_method_id'];

                    return $this;
                }
            }
        }

        return false;
    }


    /**
     * Create a Payment Methods based on the name
     *
     * @return Payment
     *
     * @throws APIExeption
     * @throws GenericException
     */
    public function create()
    {
        $insert = Curl::simple('paymentMethods/insert', $this->mapPropsToValues());

        if (isset($insert['payment_method_id'])) {
            $this->payment_method_id = $insert['payment_method_id'];
            return $this;
        }

        throw new GenericException(__('Erro ao inserir a método de pagamento') . $this->name);
    }


    /**
     * Map this object properties to an array to insert/update a moloni Payment Value
     * @return array
     */
    private function mapPropsToValues()
    {
        $values = [];

        $values['name'] = $this->name;

        return $values;
    }
}
