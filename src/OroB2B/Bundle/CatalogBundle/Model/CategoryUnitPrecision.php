<?php

namespace OroB2B\Bundle\CatalogBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

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
     * @param ProductUnit $unit
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
     * @param ProductUnit $unit
     * @return $this
     */
    public function setUnit(ProductUnit $unit = null)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnit()
    {
        return $this->getUnit();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode()
    {
        if ($this->getUnit()) {
            return $this->getUnit()->getCode();
        } else {
            return null;
        }
    }
}
