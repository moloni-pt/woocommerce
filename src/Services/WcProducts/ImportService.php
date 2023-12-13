<?php

namespace Moloni\Services\WcProducts;

abstract class ImportService implements ImportServiceInterface
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
