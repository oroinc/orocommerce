<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\FlushableCache;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlCacheAwareProvider;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlDatabaseAwareProvider;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class SluggableUrlDatabaseAwareProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var SluggableUrlCacheAwareProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheProvider;

    /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(SluggableUrlCacheAwareProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(UrlCacheInterface::class);
    }

    /**
     * @dataProvider cacheDataProvider
     * @param UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache
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
                    'slug_prototype' => 'slug-url'
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
                    $name,
                    $routeParameters,
                    '/slug-url',
                    'slug-url',
                    $localizationId
                ],
                [
                    SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY,
                    [],
                    json_encode([$name => true]),
                    null
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

    /**
     * @dataProvider getDefaultCacheValues
     *
     * @param int $localizationId
     * @param array $expected
     */
    public function testGetUrlWithCacheUrl($localizationId, $expected)
    {
        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $this->cache,
            $this->registry
        );
        $provider->setContextUrl('');

        $name = 'oro_product_view';
        $params = ['id' => 10];

        $this->cache->expects($this->once())
            ->method('has')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY)
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => true]));
        $this->cacheProvider->expects($this->exactly($expected['calls']))
            ->method('getUrl')
            ->withConsecutive(
                [$name, $params, $localizationId],
                [$name, $params, SluggableUrlGenerator::DEFAULT_LOCALIZATION_ID],
                [$name, $params, $localizationId]
            )
            ->willReturnOnConsecutiveCalls(
                $expected['url'],
                $expected['defaultUrl'],
                $expected['finalUrl']
            );

        $this->assertEquals('/slug-url', $provider->getUrl($name, $params, $localizationId));
    }

    /**
     * @return array
     */
    public function getDefaultCacheValues()
    {
        return [
            'default locale' => [
                'localizationId' => 0,
                'expected' => [
                    'calls' => 1,
                    'url' => '/slug-url',
                    'defaultUrl' => '/slug-url',
                    'finalUrl' => '/slug-url',
                ]
            ],
            'cached default value' => [
                'localizationId' => 1,
                'expected' => [
                    'calls' => 1,
                    'url' => '/slug-url',
                    'defaultUrl' => '/slug-url',
                    'finalUrl' => '/slug-url',
                ]
            ],
            'cached localized value' => [
                'localizationId' => 1,
                'expected' => [
                    'calls' => 1,
                    'url' => '/slug-url',
                    'defaultUrl' => '/default-slug-url',
                    'finalUrl' => '/slug-url',
                ]
            ]
        ];
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
            ->method('has')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, []);

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
                    $name,
                    $routeParameters,
                    null,
                    null,
                    $localizationId
                ],
                [
                    SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY,
                    [],
                    json_encode([$name => false]),
                    null
                ]
            );

        $this->assertNull($provider->getUrl($name, $routeParameters, $localizationId));
    }

    public function testGetUrlRouteNotSupported()
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
            ->method('has')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY)
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => false]));

        $this->cacheProvider->expects($this->never())
            ->method($this->anything());

        $this->assertNull($provider->getUrl($name, $routeParameters, $localizationId));
    }
}
