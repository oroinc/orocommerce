<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductPriceChangeTriggerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testConstructor()
    {
        $priceList = new PriceList();
        $product = new Product();
        $productPriceChangeTrigger = new ProductPriceChangeTrigger($priceList, $product);

        $this->assertSame($priceList, $productPriceChangeTrigger->getPriceList());
        $this->assertSame($product, $productPriceChangeTrigger->getProduct());
    }

    /**
     * @param null|int $productId
     * @param null|int $priceListId
     * @return ProductPriceChangeTrigger
     */
    protected function getProductPriceChangeTrigger($productId = null, $priceListId = null)
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        /** @var PriceList $priceList */
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => $priceListId]);

        return new ProductPriceChangeTrigger($priceList, $product);
    }
}
