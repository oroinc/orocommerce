<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SEOBundle\Layout\DataProvider\SeoDataProvider;
use Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub\ProductStub;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SeoDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var SeoDataProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new SeoDataProvider(
            $this->localizationHelper,
            $this->requestStack,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testGetMetaInformationHasVariantWithSeoData()
    {
        $node = new ContentNodeStub();
        $node->addMetaDescriptions((new LocalizedFallbackValue())->setString('descr'));

        $contentVariant = $this->createMock(ContentNodeAwareInterface::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($node);

        $request = Request::create('/');
        $request->attributes->set('_content_variant', $contentVariant);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);
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

        $contentVariant = $this->createMock(ContentNodeAwareInterface::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($node);

        $request = Request::create('/');
        $request->attributes->set('_content_variant', $contentVariant);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);
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
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);
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

    public function testGetMetaInformationWithoutRequest()
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
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
}
