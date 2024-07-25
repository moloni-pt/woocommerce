<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Exceptions\APIExeption;
use Moloni\Exceptions\GenericException;

/**
 * Class Product Category
 * @package Moloni\Controllers
 */
class ProductCategory
{

    public $name;
    public $category_id;
    public $parent_id = 0;

    /**
     * Product Category constructor.
     * @param string $name
     * @param int $parentId
     */
    public function __construct($name, $parentId = 0)
    {
        $this->name = wp_specialchars_decode(trim($name));
        $this->parent_id = $parentId;
    }

    /**
     * This method SHOULD be replaced by a productCategories/getBySearch
     *
     * @throws APIException
     */
    public function loadByName()
    {
        $categoriesList = Curl::simple('productCategories/getByName', [
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'exact' => 1
        ]);

        if (!empty($categoriesList) && is_array($categoriesList)) {
            $this->category_id = $categoriesList[0]['category_id'];
            return $this;
        }

        return false;
    }

    /**
     * Create a product based on a WooCommerce Product
     *
     * @return ProductCategory
     * @throws GenericException
     */
    public function create(): ProductCategory
    {
        try {
            $insert = Curl::simple('productCategories/insert', $this->mapPropsToValues());
        } catch (APIException $e) {
            throw new GenericException(__('Erro ao inserir a categoria') . ' ' . $this->name, $e->getData());
        }

        if (!isset($insert['category_id'])) {
             throw new GenericException(__('Erro ao inserir a categoria') . ' ' . $this->name);
        }

        $this->category_id = $insert['category_id'];

        return $this;
    }


    /**
     * Map this object properties to an array to insert/update a moloni product category
     * @return array
     */
    private function mapPropsToValues()
    {
        $values = [];

        $values['name'] = $this->name;
        $values['parent_id'] = $this->parent_id;

        return $values;
    }
}
