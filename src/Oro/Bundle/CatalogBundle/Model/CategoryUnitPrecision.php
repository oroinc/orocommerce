<?php

namespace Oro\Bundle\CatalogBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

class CategoryUnitPrecision implements ProductUnitHolderInterface
{
    /**
     * @var integer
     */
    protected $precision;

    /**
     * @var ProductUnit
     */
    protected $unit;

    /**
     * @param integer $precision
     * @param ProductUnit|null $unit
     * @return CategoryUnitPrecision
     */
    public static function create($precision, ProductUnit $unit = null)
    {
        /* @var $categoryUnitPrecision self */
        $categoryUnitPrecision = new static();
        $categoryUnitPrecision->setPrecision($precision)->setUnit($unit);

        return $categoryUnitPrecision;
    }

    /**
     * @return integer
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param integer $precision
     * @return $this
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit|null $unit
     * @return $this
     */
    public function setUnit(ProductUnit $unit = null)
    {
        $this->unit = $unit;

        return $this;
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return null;
    }

    public function getProduct()
    {
        return null;
    }

    #[\Override]
    public function getProductHolder()
    {
        return $this;
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->getUnit();
    }

    #[\Override]
    public function getProductUnitCode()
    {
        if ($this->getUnit()) {
            return $this->getUnit()->getCode();
        } else {
            return null;
        }
    }
}
