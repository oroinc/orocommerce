<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ProductsContentVariantProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductsContentVariantProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductsContentVariantProvider();
    }

    public function testSupportedClass()
    {
        $this->assertTrue($this->provider->isSupportedClass(Product::class));
        $this->assertFalse($this->provider->isSupportedClass('Test'));
    }

    public function testGetRecordId()
    {
        $array['categoryProductId'] = 1;
        $this->assertEquals($array['categoryProductId'], $this->provider->getRecordId($array));
    }

    public function testGetLocalizedValues()
    {
        $node = $this->createMock(ContentNodeInterface::class);
        $expected = 0;
        $this->assertCount($expected, $this->provider->getLocalizedValues($node));
    }

    public function testGetValues()
    {
        $node = $this->createMock(ContentNodeInterface::class);
        $expected = 0;
        $this->assertCount($expected, $this->provider->getValues($node));
    }
}
