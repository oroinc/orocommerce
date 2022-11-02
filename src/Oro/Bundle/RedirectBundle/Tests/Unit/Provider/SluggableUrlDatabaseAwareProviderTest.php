<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlCacheAwareProvider;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlDatabaseAwareProvider;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;

class SluggableUrlDatabaseAwareProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SluggableUrlCacheAwareProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(SluggableUrlCacheAwareProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(UrlCacheInterface::class);
    }

    public function testGetUrlNull()
    {
        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $this->cache,
            $this->doctrine
        );
        $provider->setContextUrl('');

        $name = 'oro_product_view';
        $params = ['id' => 10];
        $localizationId = 1;

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => true], JSON_THROW_ON_ERROR));
        $this->cacheProvider->expects($this->once())
            ->method('getUrl')
            ->with($name, $params, $localizationId)
            ->willReturn(null);

        $this->assertNull($provider->getUrl($name, $params, $localizationId));
    }

    /**
     * @dataProvider cacheDataProvider
     */
    public function testGetUrlWithoutCacheUrl(UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache)
    {
        if ($cache instanceof FlushableCacheInterface) {
            $cache->expects($this->once())
                ->method('flushAll');
        }

        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $cache,
            $this->doctrine
        );
        $provider->setContextUrl('');

        $name = 'oro_product_view';
        $routeParameters = ['id' => 10];

        $localizationId = 1;

        $cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => true], JSON_THROW_ON_ERROR));

        $this->cacheProvider->expects($this->exactly(2))
            ->method('getUrl')
            ->with($name, $routeParameters, $localizationId)
            ->willReturnOnConsecutiveCalls(
                false,
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

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturnSelf();
        $this->doctrine->expects($this->once())
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
                    json_encode([$name => true], JSON_THROW_ON_ERROR),
                    null
                ]
            );

        $this->assertEquals('/slug-url', $provider->getUrl($name, $routeParameters, $localizationId));
    }

    public function cacheDataProvider(): array
    {
        return [
            'simple cache' => [$this->createMock(UrlCacheInterface::class)],
            'flushable cache' => [$this->createMock(UrlCacheAllCapabilities::class)]
        ];
    }

    /**
     * @dataProvider getDefaultCacheValues
     */
    public function testGetUrlWithCacheUrl(int $localizationId, array $expected)
    {
        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $this->cache,
            $this->doctrine
        );
        $provider->setContextUrl('');

        $name = 'oro_product_view';
        $params = ['id' => 10];

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => true], JSON_THROW_ON_ERROR));
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

    public function getDefaultCacheValues(): array
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
            $this->doctrine
        );
        $routeParameters = [
            'id' => 10
        ];
        $name = 'oro_product_view';

        $localizationId = 1;

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(false);

        $this->cacheProvider->expects($this->exactly(2))
            ->method('getUrl')
            ->with($name, $routeParameters, $localizationId)
            ->willReturn(false);

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('getRawSlug')
            ->with($name, $routeParameters, $localizationId)
            ->willReturn(null);
        $slugRepository->expects($this->any())
            ->method('getUsedRoutes')
            ->willReturn([$name]);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturnSelf();
        $this->doctrine->expects($this->once())
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
                    json_encode([$name => false], JSON_THROW_ON_ERROR),
                    null
                ]
            );

        $this->assertFalse($provider->getUrl($name, $routeParameters, $localizationId));
    }

    public function testGetUrlRouteNotSupported()
    {
        $provider = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $this->cache,
            $this->doctrine
        );
        $routeParameters = [
            'id' => 10
        ];
        $name = 'oro_product_view';

        $localizationId = 1;

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, [])
            ->willReturn(json_encode([$name => false], JSON_THROW_ON_ERROR));

        $this->cacheProvider->expects($this->never())
            ->method($this->anything());

        $this->assertNull($provider->getUrl($name, $routeParameters, $localizationId));
    }
}
