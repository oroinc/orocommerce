<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Provider\CategoryContextUrlProvider;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryContextUrlProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var CategoryContextUrlProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMockBuilder(UrlStorageCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new CategoryContextUrlProvider($this->requestStack, $this->cache);
    }

    public function testGetUrlByRequest()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testGetUrlFromCacheNonCategoryRoute()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testGetUrlFromCacheNonCategoryIdInRoute()
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testGetUrlFromCacheSlugIsNull()
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $slug = null;
        $request->attributes = new ParameterBag([CategoryContextUrlProvider::USED_SLUG_KEY => $slug]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNoRequestAttribute()
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->attributes = new ParameterBag();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNoRequest()
    {
        $categoryId = 2;
        $categoryUrl = '/category';

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->assertCacheCall($categoryId, $categoryUrl);

        $this->assertEquals($categoryUrl, $this->provider->getUrl($categoryId));
    }

    public function testGetUrlFromCacheNoRequestNoUrlInCache()
    {
        $categoryId = 2;

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(
                CategoryContextUrlProvider::CATEGORY_ROUTE_NAME,
                [
                    CategoryContextUrlProvider::CATEGORY_ID => $categoryId,
                    CategoryContextUrlProvider::INCLUDE_SUBCATEGORIES => true
                ]
            )
            ->willReturn(null);

        $this->assertNull($this->provider->getUrl($categoryId));
    }

    /**
     * @param int $categoryId
     * @param string $categoryUrl
     */
    private function assertCacheCall($categoryId, $categoryUrl)
    {
        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(
                CategoryContextUrlProvider::CATEGORY_ROUTE_NAME,
                [
                    CategoryContextUrlProvider::CATEGORY_ID => $categoryId,
                    CategoryContextUrlProvider::INCLUDE_SUBCATEGORIES => true
                ]
            )
            ->willReturn($categoryUrl);
    }
}
