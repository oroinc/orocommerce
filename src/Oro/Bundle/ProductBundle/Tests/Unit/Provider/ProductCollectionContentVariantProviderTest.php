<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductCollectionContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ProductCollectionContentVariantProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductsContentVariantProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductCollectionContentVariantProvider();
    }

    public function testSupportedClass()
    {
        $this->assertTrue($this->provider->isSupportedClass(Product::class));
        $this->assertFalse($this->provider->isSupportedClass('Test'));
    }

    public function testGetRecordId()
    {
        $array['productCollectionProductId'] = 1;
        $this->assertEquals($array['productCollectionProductId'], $this->provider->getRecordId($array));
    }

    public function testGetLocalizedValues()
    {
        $node = $this->createMock(ContentNodeInterface::class);
        $this->assertEquals([], $this->provider->getLocalizedValues($node));
    }

    public function testGetValues()
    {
        $node = $this->createMock(ContentNodeInterface::class);
        $this->assertEquals([], $this->provider->getValues($node));
    }
}
