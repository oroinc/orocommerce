<?php

namespace Oro\Bundle\ProductBundle\Tests\UnitProvider;

use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

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
            Product::STATUS_DISABLED => 'oro.product.status.disabled',
            Product::STATUS_ENABLED => 'oro.product.status.enabled'
        ];

        $this->assertEquals($expected, $this->productStatusProvider->getAvailableProductStatuses());
    }
}
