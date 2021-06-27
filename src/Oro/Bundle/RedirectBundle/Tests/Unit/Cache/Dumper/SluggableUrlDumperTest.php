<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache\Dumper;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;

class SluggableUrlDumperTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $slugRepository;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->slugRepository = $this->createMock(SlugRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->slugRepository);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
    }

    public function testDump()
    {
        /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(UrlCacheInterface::class);
        $dumper = new SluggableUrlDumper($this->registry, $cache);

        $routeName = 'test';
        $ids = [1, 2];

        $slugs = [
            [
                'routeName' => 'route1',
                'routeParameters' => ['routeParameter1' => 1],
                'url' => '/test/url1',
                'slugPrototype' => 'url1',
                'localization_id' => 1
            ],
            [
                'routeName' => 'route2',
                'routeParameters' => ['routeParameter2' => 2],
                'url' => '/test/url2',
                'slugPrototype' => 'url2',
                'localization_id' => null
            ]
        ];

        $this->slugRepository->expects($this->once())
            ->method('getSlugDataForDirectUrls')
            ->with($ids)
            ->willReturn($slugs);

        $cache->expects($this->exactly(2))
            ->method('setUrl')
            ->withConsecutive(
                [$routeName, ['routeParameter1' => 1], '/test/url1', 'url1', 1],
                [$routeName, ['routeParameter2' => 2], '/test/url2', 'url2', null]
            );

        $dumper->dump($routeName, $ids);
    }

    public function testDumpFlushableCache()
    {
        /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(UrlCacheAllCapabilities::class);
        $dumper = new SluggableUrlDumper($this->registry, $cache);

        $routeName = 'test';
        $ids = [1, 2];

        $slugs = [
            [
                'routeName' => 'route1',
                'routeParameters' => ['routeParameter1' => 1],
                'url' => '/test/url1',
                'slugPrototype' => 'url1',
                'localization_id' => 1
            ]
        ];

        $this->slugRepository->expects($this->once())
            ->method('getSlugDataForDirectUrls')
            ->with($ids)
            ->willReturn($slugs);

        $cache->expects($this->once())
            ->method('flushAll');

        $cache->expects($this->once())
            ->method('setUrl')
            ->with($routeName, ['routeParameter1' => 1], '/test/url1', 'url1', 1);

        $dumper->dump($routeName, $ids);
    }
}
