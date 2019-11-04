<?php

namespace Moloni\Controllers;

use Moloni\Curl;
use Moloni\Error;

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
        $this->name = trim($name);
        $this->parent_id = $parentId;
    }

    /**
     * This method SHOULD be replaced by a productCategories/getBySearch
     * @throws Error
     */
    public function loadByName()
    {
        $categoriesList = Curl::simple("productCategories/getAll", ["parent_id" => $this->parent_id]);
        if (!empty($categoriesList) && is_array($categoriesList)) {
            foreach ($categoriesList as $category) {
                if ($category['name'] == $this->name) {
                    $this->category_id = $category['category_id'];
                    return $this;
                }
            }
        }

        return false;
    }

    /**
     * Create a product based on a WooCommerce Product
     * @throws Error
     */
    public function create()
    {
        $insert = Curl::simple("productCategories/insert", $this->mapPropsToValues());

        if (isset($insert['category_id'])) {
            $this->category_id = $insert['category_id'];
            return $this;
        }

        throw new Error(__("Erro ao inserir a categoria") . $this->name);
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