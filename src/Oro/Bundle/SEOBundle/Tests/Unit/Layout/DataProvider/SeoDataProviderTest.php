<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SEOBundle\Layout\DataProvider\SeoDataProvider;
use Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub\ProductStub;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;

class SeoDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var RequestWebContentVariantProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $requestWebContentVariantProvider;

    /** @var SeoDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->requestWebContentVariantProvider = $this->createMock(RequestWebContentVariantProvider::class);

        $this->provider = new SeoDataProvider(
            $this->localizationHelper,
            $this->requestWebContentVariantProvider,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testGetMetaInformationHasVariantWithSeoData()
    {
        $node = new ContentNodeStub();
        $node->addMetaDescriptions((new LocalizedFallbackValue())->setString('descr'));

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($node);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);
        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                function (Collection $values) {
                    return $values->first();
                }
            );

        $data = new ProductStub();
        $data->addMetaDescriptions((new LocalizedFallbackValue())->setString('product descr'));

        $this->assertEquals('descr', (string)$this->provider->getMetaInformation($data, 'metaDescriptions'));
    }

    public function testGetMetaInformationHasVariantWithoutSeoData()
    {
        $node = new ContentNodeStub();

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($node);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);
        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                function (Collection $values) {
                    return $values->first();
                }
            );

        $data = new ProductStub();
        $data->addMetaDescriptions((new LocalizedFallbackValue())->setString('product descr'));

        $this->assertEquals('product descr', (string)$this->provider->getMetaInformation($data, 'metaDescriptions'));
    }

    public function testGetMetaInformationWithoutVariant()
    {
        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn(null);
        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                function (Collection $values) {
                    return $values->first();
                }
            );

        $data = new ProductStub();
        $data->addMetaDescriptions((new LocalizedFallbackValue())->setString('product descr'));

        $this->assertEquals('product descr', (string)$this->provider->getMetaInformation($data, 'metaDescriptions'));
    }

    public function testGetMetaInformationFromContentNodeWithSeoData()
    {
        $node = new ContentNodeStub();
        $node->addMetaDescriptions((new LocalizedFallbackValue())->setString('descr'));

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($node);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);
        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                function (Collection $values) {
                    return $values->first();
                }
            );
        $this->assertEquals('descr', (string)$this->provider->getMetaInformationFromContentNode('metaDescriptions'));
    }

    public function testGetMetaInformationFromContentNodeWithoutSeoData()
    {
        $node = new ContentNodeStub();

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($node);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);
        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                function (Collection $values) {
                    return $values->first();
                }
            );
        $this->assertEquals(null, (string)$this->provider->getMetaInformationFromContentNode('metaDescriptions'));
    }

    public function testGetMetaInformationFromContentNodeWithoutNode()
    {
        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn(null);

        $this->assertEquals(null, (string)$this->provider->getMetaInformationFromContentNode('metaDescriptions'));
    }
}
