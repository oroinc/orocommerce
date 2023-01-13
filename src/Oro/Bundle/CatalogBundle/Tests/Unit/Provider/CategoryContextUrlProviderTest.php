<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Provider\CategoryContextUrlProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryContextUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var LocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    /** @var CategoryContextUrlProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->cache = $this->createMock(UrlCacheInterface::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);

        $this->provider = new CategoryContextUrlProvider(
            $this->requestStack,
            $this->cache,
            $this->localizationProvider
        );
    }

    public function testGetUrlByRequest(): void
    {
        $request = $this->createMock(Request::class);

        $categoryId = 2;
        $url = '/my-category';

        $slug = new Slug();
        $slug->setRouteName(CategoryContextUrlProvider::CATEGORY_ROUTE_NAME);
        $slug->setRouteParameters([CategoryContextUrlProvider::CATEGORY_ID => $categoryId]);
        $slug->setUrl($url);
        $request->attributes = new ParameterBag([CategoryContextUrlProvider::USED_SLUG_KEY => $slug]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals($url, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNonCategoryRoute(): void
    {
        $request = $this->createMock(Request::class);

        $categoryId = 2;
        $url = '/my-url';
        $categoryUrl = '/category';

        $slug = new Slug();
        $slug->setRouteName('some_route');
        $slug->setRouteParameters(['id' => $categoryId]);
        $slug->setUrl($url);
        $request->attributes = new ParameterBag([CategoryContextUrlProvider::USED_SLUG_KEY => $slug]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNonCategoryIdInRoute(): void
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $request = $this->createMock(Request::class);

        $slug = new Slug();
        $slug->setRouteName(CategoryContextUrlProvider::CATEGORY_ROUTE_NAME);
        $slug->setRouteParameters([CategoryContextUrlProvider::CATEGORY_ID => 1000]);
        $slug->setUrl('/my-url');
        $request->attributes = new ParameterBag([CategoryContextUrlProvider::USED_SLUG_KEY => $slug]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheSlugIsNull(): void
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $request = $this->createMock(Request::class);

        $slug = null;
        $request->attributes = new ParameterBag([CategoryContextUrlProvider::USED_SLUG_KEY => $slug]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNoRequestAttribute(): void
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNoRequest(): void
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNoRequestNoUrlInCache(): void
    {
        $categoryId = 2;

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);
        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(
                CategoryContextUrlProvider::CATEGORY_ROUTE_NAME,
                [
                    CategoryContextUrlProvider::CATEGORY_ID => $categoryId,
                    CategoryContextUrlProvider::INCLUDE_SUBCATEGORIES => true
                ],
                $localizationId
            )
            ->willReturn(null);

        $this->assertNull($this->provider->getUrl($categoryId));
    }

    private function assertCacheCall($categoryId, $categoryUrl): void
    {
        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);
        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(
                CategoryContextUrlProvider::CATEGORY_ROUTE_NAME,
                [
                    CategoryContextUrlProvider::CATEGORY_ID => $categoryId,
                    CategoryContextUrlProvider::INCLUDE_SUBCATEGORIES => true
                ],
                $localizationId
            )
            ->willReturn($categoryUrl);
    }
}
