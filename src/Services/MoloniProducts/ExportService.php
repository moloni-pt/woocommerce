<?php

namespace Moloni\Services\MoloniProducts;

abstract class ExportService implements ExportServiceInterface
{
    protected $wcProduct;
    protected $moloniProduct;

    //            Gets            //

    public function getMoloniProduct(): array
    {
        return $this->moloniProduct;
    }

    public function getWcProduct()
    {
        return $this->wcProduct;
    }
}
