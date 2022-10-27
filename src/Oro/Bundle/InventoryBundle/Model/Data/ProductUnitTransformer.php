<?php

namespace Oro\Bundle\InventoryBundle\Model\Data;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitTransformer
{
    /** @var array $unitsCache */
    protected $unitsCache = [];

    /** @var  ProductUnitsProvider $productUnitsProvider */
    protected $productUnitsProvider;
    private Inflector $inflector;

    public function __construct(ProductUnitsProvider $productUnitsProvider, Inflector $inflector)
    {
        $this->productUnitsProvider = $productUnitsProvider;
        $this->inflector = $inflector;
    }

    /**
     * Based on the full name of a unit return its code
     * @param string $unit
     * @return null|string
     */
    public function transformToProductUnit($unit)
    {
        $unit = $this->inflector->singularize($unit);

        foreach ($this->getUnits() as $name => $code) {
            if ($unit == $name) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Return array of available product units
     * @return array
     */
    protected function getUnits()
    {
        if (empty($this->unitsCache)) {
            $this->unitsCache = $this->productUnitsProvider->getAvailableProductUnits();
        }

        return $this->unitsCache;
    }
}
