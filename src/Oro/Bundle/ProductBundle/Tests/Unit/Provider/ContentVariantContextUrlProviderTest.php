<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Provider\ContentVariantContextUrlProvider;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentVariantContextUrlProviderTest extends TestCase
{
    use EntityTrait;

    const DATA = 'someData';

    private RequestStack|MockObject $requestStack;

    private UrlCacheInterface|MockObject $cache;

    private LocalizationProviderInterface|MockObject $localizationProvider;

    private ContentVariantContextUrlProvider $provider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->cache = $this->createMock(UrlCacheInterface::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->provider = new ContentVariantContextUrlProvider(
            $this->requestStack,
            $this->cache,
            $this->localizationProvider
        );
    }

    public function testGetUrlWithoutRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $url = 'URL';
        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);
        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(
                ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME,
                [ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::DATA],
                $localizationId
            )
            ->willReturn($url);

        $this->assertEquals('URL', $this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithoutUsedSlugKey()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithNoSlugByKey()
    {
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => new \stdClass()]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithWrongRouteName()
    {
        $slug = new Slug();
        $slug->setRouteName('someRouteName');
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithoutContentVariantIdKey()
    {
        $slug = new Slug();
        $slug->setRouteName(ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME);
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithWrongContentVariantIdKey()
    {
        $slug = new Slug();
        $slug->setRouteName(ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME);
        $slug->setRouteParameters([ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => 'someWrongData']);
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrl()
    {
        $url = 'http://test.url';
        $slug = new Slug();
        $slug->setUrl($url);
        $slug->setRouteName(ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME);
        $slug->setRouteParameters([ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::DATA]);
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals($url, $this->provider->getUrl(self::DATA));
    }
}
