<?php

namespace Oro\Bundle\SeoCatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SEOBundle\Layout\DataProvider\SeoDataProvider;
use Oro\Bundle\SEOBundle\Layout\DataProvider\SeoTitleDataProvider;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProvider;

class SeoTitleDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SeoDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $seoDataProvider;

    /**
     * @var TitleDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleDataProvider;

    /**
     * @var SeoTitleDataProvider
     */
    protected $seoTitleDataProvider;

    protected function setUp()
    {
        $this->titleDataProvider = $this->getMockBuilder(TitleDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->seoDataProvider = $this->getMockBuilder(SeoDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->seoTitleDataProvider = new SeoTitleDataProvider($this->seoDataProvider, $this->titleDataProvider);
    }

    public function testGetTitleFromSeoProviderForContentNode()
    {
        $default = 'default';
        $expectedTitle = 'test metaTitle for content node';

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString('test metaTitle for content node');

        $this->seoDataProvider->expects($this->once())
            ->method('getMetaInformationFromContentNode')
            ->willReturn($localizedFallbackValue);

        $this->seoDataProvider->expects($this->never())
            ->method('getMetaInformation');

        $this->assertEquals($expectedTitle, $this->seoTitleDataProvider->getTitle($default, null));
    }

    public function testGetTitleFromSeoProviderForPassedEntity()
    {
        $default = 'default';
        $expectedTitle = 'test metaTitle for entity';

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString('test metaTitle for entity');

        $category = $this->createMock(Category::class);

        $this->seoDataProvider->expects($this->never())
            ->method('getMetaInformationFromContentNode');

        $this->seoDataProvider->expects($this->once())
            ->method('getMetaInformation')
            ->willReturn($localizedFallbackValue);

        $this->assertEquals($expectedTitle, $this->seoTitleDataProvider->getTitle($default, $category));
    }

    public function testGetTitleWithoutContentNode()
    {
        $default = 'default';

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString('test metaTitle for entity');

        $this->titleDataProvider->expects($this->once())
            ->method('getTitle')
            ->with($default)
            ->willReturn($default);

        $this->seoDataProvider->expects($this->once())
            ->method('getMetaInformationFromContentNode')
            ->willReturn(null);

        $this->seoDataProvider->expects($this->never())
            ->method('getMetaInformation');

        $this->assertEquals($default, $this->seoTitleDataProvider->getTitle($default, null));
    }

    public function testGetNodeTitle()
    {
        $default = 'default';

        $this->titleDataProvider->expects($this->once())
            ->method('getNodeTitle')
            ->with($default)
            ->willReturn($default);

        $this->assertEquals($default, $this->seoTitleDataProvider->getNodeTitle($default));
    }
}
