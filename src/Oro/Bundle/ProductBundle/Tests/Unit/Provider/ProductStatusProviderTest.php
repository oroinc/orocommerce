<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;

class ProductStatusProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductStatusProvider */
    protected $productStatusProvider;

    protected function setUp(): void
    {
        $this->productStatusProvider = new ProductStatusProvider();
    }

    public function testGetAvailableProductStatus()
    {
        $expected = [
            'oro.product.status.disabled' => Product::STATUS_DISABLED,
            'oro.product.status.enabled' => Product::STATUS_ENABLED,
        ];

        $this->assertEquals($expected, $this->productStatusProvider->getAvailableProductStatuses());
    }
}
