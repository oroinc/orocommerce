<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlCacheAwareProvider;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlDatabaseAwareProvider;

class SluggableUrlDatabaseAwareProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SluggableUrlDatabaseAwareProvider */
    protected $testable;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var SluggableUrlCacheAwareProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cacheProvider;

    /** @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(SluggableUrlCacheAwareProvider::class);

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->cache = $this->createMock(UrlStorageCache::class);

        $this->testable = new SluggableUrlDatabaseAwareProvider(
            $this->cacheProvider,
            $this->cache,
            $this->registry
        );
    }

    public function testGetUrlWithoutCacheUrl()
    {
        $this->testable->setContextUrl('');

        $name = 'oro_product_view';
        $routeParameters = ['id' => 10];

        $localizationId = 1;

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

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturnSelf();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($slugRepository);

        $storage = $this->createMock(UrlDataStorage::class);
        $storage->expects($this->once())
            ->method('setUrl')
            ->with(
                $routeParameters,
                '/slug-url',
                'slug-url',
                $localizationId
            );

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($name, $routeParameters)
            ->willReturn($storage);

        $this->cache->expects($this->once())
            ->method('flush');

        $this->assertEquals('/slug-url', $this->testable->getUrl($name, $routeParameters, $localizationId));
    }

    public function testGetUrlWithCacheUrl()
    {
        $this->testable->setContextUrl('');

        $name = 'oro_product_view';
        $params = ['id' => 10];

        $localizationId = 1;

        $this->cacheProvider->expects($this->once())
            ->method('getUrl')
            ->with($name, $params, $localizationId)
            ->willReturn('/slug-url');

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->cache->expects($this->never())
            ->method('getUrlDataStorage');

        $this->assertEquals('/slug-url', $this->testable->getUrl($name, $params, $localizationId));
    }

    public function testGetUrlNoCacheNoDatabase()
    {
        $routeParameters = [
            'id' => 10
        ];
        $name = 'oro_product_view';

        $localizationId = 1;

        $this->cacheProvider->expects($this->exactly(2))
            ->method('getUrl')
            ->with($name, $routeParameters, $localizationId)
            ->willReturn(null);

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('getRawSlug')
            ->with($name, $routeParameters, $localizationId)
            ->willReturn(null);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturnSelf();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($slugRepository);

        $storage = $this->createMock(UrlDataStorage::class);
        $storage->expects($this->once())
            ->method('setUrl')
            ->with(
                $routeParameters,
                false,
                false,
                $localizationId
            );

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($name, $routeParameters)
            ->willReturn($storage);

        $this->cache->expects($this->once())
            ->method('flush');

        $this->assertNull($this->testable->getUrl(
            $name,
            $routeParameters,
            $localizationId
        ));
    }
}
