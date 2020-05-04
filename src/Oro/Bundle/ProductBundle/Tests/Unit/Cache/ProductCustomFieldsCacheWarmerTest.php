<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Cache;

use Oro\Bundle\ProductBundle\Cache\ProductCustomFieldsCacheWarmer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomFieldsCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $customFieldProvider;

    /** @var ProductCustomFieldsCacheWarmer */
    private $warmer;

    protected function setUp(): void
    {
        $this->customFieldProvider = $this->createMock(CustomFieldProvider::class);

        $this->warmer = new ProductCustomFieldsCacheWarmer($this->customFieldProvider);
    }

    public function testWarmUp(): void
    {
        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with(Product::class);

        $this->warmer->warmUp('cache/dir');
    }

    public function testIsOptional(): void
    {
        $this->assertTrue($this->warmer->isOptional());
    }
}
