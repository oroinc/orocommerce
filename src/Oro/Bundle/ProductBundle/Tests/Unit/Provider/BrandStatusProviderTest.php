<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\BrandStatusProvider;

class BrandStatusProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var BrandStatusProvider $brandStatusProvider */
    protected $brandStatusProvider;

    public function setup()
    {
        $this->brandStatusProvider = new BrandStatusProvider();
    }

    public function testGetAvailableBrandStatus()
    {
        $expected = [
            Product::STATUS_DISABLED => 'oro.product.brand.status.disabled',
            Product::STATUS_ENABLED => 'oro.product.brand.status.enabled'
        ];

        $this->assertEquals($expected, $this->brandStatusProvider->getAvailableBrandStatuses());
    }
}
