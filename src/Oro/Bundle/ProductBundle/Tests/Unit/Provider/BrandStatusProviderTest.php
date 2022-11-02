<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\BrandStatusProvider;

class BrandStatusProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var BrandStatusProvider */
    protected $brandStatusProvider;

    protected function setUp(): void
    {
        $this->brandStatusProvider = new BrandStatusProvider();
    }

    public function testGetAvailableBrandStatus()
    {
        $expected = [
            'oro.product.brand.status.disabled' => Product::STATUS_DISABLED,
            'oro.product.brand.status.enabled' => Product::STATUS_ENABLED,
        ];

        $this->assertEquals($expected, $this->brandStatusProvider->getAvailableBrandStatuses());
    }
}
