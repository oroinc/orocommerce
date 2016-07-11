<?php

namespace OroB2B\Bundle\WarehouseBundle\Model\Data;

use Doctrine\Common\Inflector\Inflector;
use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitTransformer
{
    /** @var array $unitsCache */
    protected $unitsCache = [];

    /** @var  ProductUnitsProvider $productUnitsProvider */
    protected $productUnitsProvider;

    public function __construct(ProductUnitsProvider $productUnitsProvider)
    {
        $this->productUnitsProvider = $productUnitsProvider;
    }

    public function transformToProductUnit($unit)
    {
        $unit = Inflector::singularize($unit);

        foreach ($this->getUnits() as $code => $name) {
            if ($unit == $name) {
                return $code;
            }
        }

        return null;
    }

    protected function getUnits()
    {
        if (empty($this->unitsCache)) {
            $this->unitsCache = $this->productUnitsProvider->getAvailableProductUnits();
        }

        return $this->unitsCache;
    }
}
