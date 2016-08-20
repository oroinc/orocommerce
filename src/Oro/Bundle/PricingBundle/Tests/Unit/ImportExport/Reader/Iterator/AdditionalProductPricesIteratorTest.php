<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Reader\Iterator;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Reader\Iterator\AdditionalProductPricesIterator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class AdditionalProductPricesIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['EUR', 'USD']);

        $unitOne = (new ProductUnit())->setCode('one');
        $unitTwo = (new ProductUnit())->setCode('two');
        $unitPrecisionOne = (new ProductUnitPrecision())->setUnit($unitOne);
        $unitPrecisionTwo = (new ProductUnitPrecision())->setUnit($unitTwo);

        $productOne = (new Product())
            ->addUnitPrecision($unitPrecisionOne)
            ->addUnitPrecision($unitPrecisionTwo);

        $productTwo = (new Product())
            ->addUnitPrecision($unitPrecisionTwo);

        $productIterator = new \ArrayIterator([$productOne, $productTwo]);

        $iterator = new AdditionalProductPricesIterator($productIterator, $priceList);
        $actual = iterator_to_array($iterator);
        $expected = [
            $this->getProductPrice($priceList, $productOne, $unitOne, 'EUR'),
            $this->getProductPrice($priceList, $productOne, $unitOne, 'USD'),
            $this->getProductPrice($priceList, $productOne, $unitTwo, 'EUR'),
            $this->getProductPrice($priceList, $productOne, $unitTwo, 'USD'),
            $this->getProductPrice($priceList, $productTwo, $unitTwo, 'EUR'),
            $this->getProductPrice($priceList, $productTwo, $unitTwo, 'USD'),
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param string $currency
     * @return ProductPrice
     */
    protected function getProductPrice(PriceList $priceList, Product $product, ProductUnit $productUnit, $currency)
    {
        return (new ProductPrice())
            ->setPriceList($priceList)
            ->setProduct($product)
            ->setUnit($productUnit)
            ->setPrice(Price::create(null, $currency));
    }
}
