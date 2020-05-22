<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\Provider\ContentNodeContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ContentNodeContentVariantProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductsContentVariantProvider
     */
    protected $provider;

    protected function setUp(): void
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
                    'getMetaTitles',
                    'getMetaDescriptions',
                    'getMetaKeywords'
                ]
            )
            ->getMockForAbstractClass();
        $node->expects($this->once())->method('getMetaTitles')->withAnyParameters()->willReturn(['array']);
        $node->expects($this->once())->method('getMetaDescriptions')->withAnyParameters()->willReturn(['array']);
        $node->expects($this->once())->method('getMetaKeywords')->willReturn(['keywords']);

        $result = $this->provider->getLocalizedValues($node);
        $expectedCount = 3;
        $this->assertCount($expectedCount, $result);
        $this->assertEquals([
            'metaTitles'=>['array'],
            'metaDescriptions'=>['array'],
            'metaKeywords'=>['keywords'] ], $result);
    }

    public function testGetValues()
    {
        $node = $this->getMockBuilder(ContentNodeInterface::class)->getMock();
        $this->assertSame([], $this->provider->getValues($node));
    }
}
