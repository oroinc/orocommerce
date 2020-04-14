<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductContentVariantProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ContentNodeStub;

class ProductContentVariantProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductContentVariantProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductContentVariantProvider();
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->provider->isSupportedClass(Product::class));
        $this->assertFalse($this->provider->isSupportedClass('Test'));
    }

    public function testGetValues()
    {
        $this->assertEquals([], $this->provider->getValues(new ContentNodeStub(1)));
    }

    public function testGetLocalizedValues()
    {
        $this->assertEquals([], $this->provider->getLocalizedValues(new ContentNodeStub(1)));
    }

    public function testGetRecordId()
    {
        $item = ['productId' => 1];
        $this->assertEquals(1, $this->provider->getRecordId($item));
    }
}
