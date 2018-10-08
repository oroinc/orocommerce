<?php

namespace Oro\Bundle\RedirectBundle\Tests\Cache\Dumper;

use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;

class SluggableUrlDumperTest extends \PHPUnit\Framework\TestCase
{
    public function testDump()
    {
        /** @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject $slugRepository */
        $slugRepository = $this->createMock(SlugRepository::class);
        /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(UrlCacheInterface::class);
        $dumper = new SluggableUrlDumper($slugRepository, $cache);

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

        $slugRepository->expects($this->once())
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
        /** @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject $slugRepository */
        $slugRepository = $this->createMock(SlugRepository::class);
        /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(UrlCacheAllCapabilities::class);
        $dumper = new SluggableUrlDumper($slugRepository, $cache);

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

        $slugRepository->expects($this->once())
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
