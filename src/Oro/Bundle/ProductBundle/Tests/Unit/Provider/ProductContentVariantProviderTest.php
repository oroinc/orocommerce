<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductContentVariantProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ProductContentVariantProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductContentVariantProvider */
    private $provider;

    public function setUp()
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
        $contentNode = $this->getMock(ContentNode::class);
        $this->assertEquals([], $this->provider->getValues($contentNode));
    }

    public function testGetLocalizedValues()
    {
        $contentNode = $this->getMock(ContentNode::class);
        $this->assertEquals([], $this->provider->getLocalizedValues($contentNode));
    }

    public function testGetRecordId()
    {
        $item = ['productId' => 1];
        $this->assertEquals(1, $this->provider->getRecordId($item));
    }
}
