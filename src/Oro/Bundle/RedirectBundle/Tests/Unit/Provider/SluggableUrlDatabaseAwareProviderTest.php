<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\FlushableCache;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlCacheAwareProvider;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlDatabaseAwareProvider;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class SluggableUrlDatabaseAwareProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var SluggableUrlCacheAwareProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cacheProvider;

    /** @var UrlCacheInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(SluggableUrlCacheAwareProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(UrlCacheInterface::class);
    }

    /**
     * @dataProvider cacheDataProvider
     * @param UrlCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache
     */
    public function testGetUrlWithoutCacheUrl(UrlCacheInterface $cache)
    {
        if ($cache instanceof FlushableCache) {
            $cache->expects($this->once())
                ->method('flushAll');
        }

        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $cache,
            $this->registry
        );
        $provider->setContextUrl('');

        $name = 'oro_product_view';
        $routeParameters = ['id' => 10];

        $localizationId = 1;

        $cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => true]));

        $this->cacheProvider->expects($this->exactly(2))
            ->method('getUrl')
            ->with($name, $routeParameters, $localizationId)
            ->willReturnOnConsecutiveCalls(
                null,
                '/slug-url'
            );

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('getRawSlug')
            ->with($name, $routeParameters, $localizationId)
            ->willReturn(
                [
                    'url' => '/slug-url',
                    'slug_prototype' => 'slug-url',
                    'localization_id' => $localizationId
                ]
            );
        $slugRepository->expects($this->any())
            ->method('getUsedRoutes')
            ->willReturn([$name]);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturnSelf();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($slugRepository);

        $cache->expects($this->exactly(2))
            ->method('setUrl')
            ->withConsecutive(
                [
                    SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY,
                    [],
                    json_encode([$name => true]),
                    null
                ],
                [
                    $name,
                    $routeParameters,
                    '/slug-url',
                    'slug-url',
                    $localizationId
                ]
            );

        $this->assertEquals('/slug-url', $provider->getUrl($name, $routeParameters, $localizationId));
    }

    /**
     * @return array
     */
    public function cacheDataProvider()
    {
        return [
            'simple cache' => [$this->createMock(UrlCacheInterface::class)],
            'flushable cache' => [$this->createMock(UrlCacheAllCapabilities::class)]
        ];
    }

    public function testGetUrlWithCacheUrl()
    {
        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $this->cache,
            $this->registry
        );
        $provider->setContextUrl('');

        $name = 'oro_product_view';
        $params = ['id' => 10];

        $localizationId = 1;

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => true]));
        $this->cacheProvider->expects($this->once())
            ->method('getUrl')
            ->with($name, $params, $localizationId)
            ->willReturn('/slug-url');

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->any())
            ->method('getUsedRoutes')
            ->willReturn([$name]);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturnSelf();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($slugRepository);

        $this->assertEquals('/slug-url', $provider->getUrl($name, $params, $localizationId));
    }

    public function testGetUrlNoCacheNoDatabase()
    {
        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $this->cache,
            $this->registry
        );
        $routeParameters = [
            'id' => 10
        ];
        $name = 'oro_product_view';

        $localizationId = 1;

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => true]));

        $this->cacheProvider->expects($this->exactly(2))
            ->method('getUrl')
            ->with($name, $routeParameters, $localizationId)
            ->willReturn(null);

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('getRawSlug')
            ->with($name, $routeParameters, $localizationId)
            ->willReturn(null);
        $slugRepository->expects($this->any())
            ->method('getUsedRoutes')
            ->willReturn([$name]);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturnSelf();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($slugRepository);

        $this->cache->expects($this->exactly(2))
            ->method('setUrl')
            ->withConsecutive(
                [
                    SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY,
                    [],
                    json_encode([$name => true]),
                    null
                ],
                [
                    $name,
                    $routeParameters,
                    null,
                    null,
                    null
                ]
            );

        $this->assertNull($provider->getUrl($name, $routeParameters, $localizationId));
    }
}
