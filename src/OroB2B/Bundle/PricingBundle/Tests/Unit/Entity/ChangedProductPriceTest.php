<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ChangedProductPriceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testConstructor()
    {
        $priceList = new PriceList();
        $product = new Product();
        $changedProductPrice = new ChangedProductPrice($priceList, $product);

        $this->assertSame($priceList, $changedProductPrice->getPriceList());
        $this->assertSame($product, $changedProductPrice->getProduct());
    }
}
