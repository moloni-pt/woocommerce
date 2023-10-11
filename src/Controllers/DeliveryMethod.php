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

class DeliveryMethod
{
    public $delivery_method_id;
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
     * @throws APIExeption
     */
    public function loadByName()
    {
        $deliveryMethods = Curl::simple('deliveryMethods/getAll', []);

        if (!empty($deliveryMethods) && is_array($deliveryMethods)) {
            foreach ($deliveryMethods as $deliveryMethod) {
                if ($deliveryMethod['name'] === $this->name) {
                    $this->delivery_method_id = $deliveryMethod['delivery_method_id'];

                    return $this;
                }
            }
        }

        return false;
    }


    /**
     * Create a Payment Methods based on the name
     *
     * @return DeliveryMethod
     *
     * @throws GenericException
     * @throws APIExeption
     */
    public function create(): DeliveryMethod
    {
        $insert = Curl::simple('deliveryMethods/insert', $this->mapPropsToValues());

        if (isset($insert['delivery_method_id'])) {
            $this->delivery_method_id = $insert['delivery_method_id'];

            return $this;
        }

        throw new GenericException(__('Erro ao inserir a método de transporte') . $this->name);
    }


    /**
     * Map this object properties to an array to insert/update a moloni Payment Value
     *
     * @return array
     */
    private function mapPropsToValues(): array
    {
        $values = [];

        $values['name'] = $this->name;

        return $values;
    }
}
