<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Bundle\SEOBundle\Provider\ContentNodeContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ContentNodeContentVariantProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductsContentVariantProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->provider = new ContentNodeContentVariantProvider();
    }

    public function testIsSupportedClass()
    {
        $this->assertTrue($this->provider->isSupportedClass(Product::class));
        $this->assertFalse($this->provider->isSupportedClass('Test'));
    }

    public function testGetRecordId()
    {
        $array['categoryProductId'] = 1;
        $this->assertNull($this->provider->getRecordId($array));
    }

    public function testGetLocalizedValues()
    {
        $node = $this->getMockBuilder(ContentNodeInterface::class)
            ->setMethods(
                [
                    'getMetaDescriptions',
                    'getMetaKeywords'
                ]
            )
            ->getMockForAbstractClass();
        $node->expects($this->once())->method('getMetaDescriptions')->withAnyParameters()->willReturn(['array']);
        $node->expects($this->once())->method('getMetaKeywords')->willReturn(['keywords']);

        $result = $this->provider->getLocalizedValues($node);
        $expectedCount = 2;
        $this->assertCount($expectedCount, $result);
        $this->assertEquals(['metaDescriptions'=>['array'], 'metaKeywords'=>['keywords'] ], $result);
    }

    public function testGetValues()
    {
        $node = $this->getMockBuilder(ContentNodeInterface::class)->getMock();
        $this->assertSame([], $this->provider->getValues($node));
    }
}
