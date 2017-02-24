<?php

namespace Oro\Bundle\RedirectBundle\Tests\Cache\Dumper;

use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;

class SluggableUrlDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SlugRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $slugRepository;

    /**
     * @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var SluggableUrlDumper
     */
    private $dumper;

    protected function setUp()
    {
        $this->slugRepository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder(UrlStorageCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dumper = new SluggableUrlDumper(
            $this->slugRepository,
            $this->cache
        );
    }

    public function testDump()
    {
        $routeName = 'test';
        $ids = [1, 2];

        $slugs = [
            [
                'routeName' => 'route1',
                'routeParameters' => ['routeParameter1' => 1],
                'url' => '/test/url1',
                'slugPrototype' => 'url1'
            ],
            [
                'routeName' => 'route2',
                'routeParameters' => ['routeParameter2' => 2],
                'url' => '/test/url2',
                'slugPrototype' => 'url2'
            ]
        ];

        $this->slugRepository->expects($this->once())
            ->method('getSlugDataForDirectUrls')
            ->with($ids)
            ->willReturn($slugs);

        $this->cache->expects($this->exactly(2))
            ->method('setUrl')
            ->withConsecutive(
                [$routeName, ['routeParameter1' => 1], '/test/url1', 'url1'],
                [$routeName, ['routeParameter2' => 2], '/test/url2', 'url2']
            );
        $this->cache->expects($this->once())
            ->method('flush');

        $this->dumper->dump($routeName, $ids);
    }
}
