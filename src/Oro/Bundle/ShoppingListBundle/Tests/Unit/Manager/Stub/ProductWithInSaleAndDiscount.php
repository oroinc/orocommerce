<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductWithInSaleAndDiscount extends Product
{
    /**
     * @var bool
     */
    private $inSale = false;

    /**
     * @var bool
     */
    private $discount = false;

    /**
     * @var ProductUnitPrecision
     */
    private $precision;

    /**
     * @inheritdoc
     */
    public function getUnitPrecision($unitCode)
    {
        return $this->precision;
    }

    /**
     * @param ProductUnitPrecision $precision
     * @return $this
     */
    public function setUnitPrecision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInSale()
    {
        return $this->inSale;
    }

    /**
     * @param bool $inSale
     * @return $this
     */
    public function setInSale($inSale)
    {
        $this->inSale = (bool)$inSale;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDiscount()
    {
        return $this->discount;
    }

    /**
     * @param bool $discount
     * @return $this
     */
    public function setDiscount($discount)
    {
        $this->discount = (bool)$discount;

        return $this;
    }

    /**
     * @param int $id
     *
     * @return ProductWithInSaleAndDiscount
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
