<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Helper;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

trait QuoteToOrderTestTrait
{
    /**
     * @param int $id
     * @param int $priceType
     * @param float $quantity
     * @param string $unitCode
     * @param bool $isIncremented
     * @return QuoteProductOffer
     */
    protected function createOffer($id, $priceType, $quantity, $unitCode, $isIncremented = false)
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $offer = new QuoteProductOffer();

        $reflection = new \ReflectionProperty(get_class($offer), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($offer, $id);

        $offer->setPriceType($priceType)
            ->setQuantity($quantity)
            ->setProductUnit($unit)
            ->setAllowIncrements($isIncremented);

        return $offer;
    }
}
