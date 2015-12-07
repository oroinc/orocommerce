<?php
namespace Oro\Bundle\CurrencyBundle\Model;

interface PriceAwareInterface
{
    /**
     * @return Price
     */
    public function getPrice();

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price);
}
