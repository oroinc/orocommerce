<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\ProductBundle\Model\QuantityAwareInterface;

class LineItemStub implements PriceTypeAwareInterface, PriceAwareInterface, QuantityAwareInterface
{
    /**
     * @var int
     */
    protected $priceType = self::PRICE_TYPE_UNIT;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var float
     */
    protected $quantity;

    /**
     * @return int
     */
    public function getPriceType()
    {
        return $this->priceType;
    }

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int $priceType
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;
    }

    /**
     * @param float $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
