<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Provider\ProductStatusProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductStatusProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductStatusProvider $productStatusProvider */
    protected $productStatusProvider;

    public function setup()
    {
        $this->productStatusProvider = new ProductStatusProvider();
    }

    public function testGetAvailableProductStatus()
    {
        $expected = [
            Product::STATUS_DISABLED => 'orob2b.product.status.disabled',
            Product::STATUS_ENABLED => 'orob2b.product.status.enabled'
        ];

        $this->assertEquals($expected, $this->productStatusProvider->getAvailableProductStatuses());
    }
}
