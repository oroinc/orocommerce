<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

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
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        /** @var PriceList $priceList */
        $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', ['id' => $priceListId]);

        return new ProductPriceChangeTrigger($priceList, $product);
    }
}
