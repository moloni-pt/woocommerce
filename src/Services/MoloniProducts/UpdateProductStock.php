<?php

namespace Moloni\Services\MoloniProducts;

use WC_Product;

class UpdateProductStock extends ExportService
{

    public function __construct(array $moloniProduct, WC_Product $wcProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = $wcProduct;
    }

    //            Public's            //

    public function run()
    {
        // TODO: Implement run() method.
    }

    public function saveLog()
    {
        // TODO: Implement saveLog() method.
    }
}
