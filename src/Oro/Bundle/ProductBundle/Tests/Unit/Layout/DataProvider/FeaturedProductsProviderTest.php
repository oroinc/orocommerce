<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class FeaturedProductsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var FeaturedProductsProvider
     */
    private $provider;

    /**
     * @var SegmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $segmentManager;

    /**
     * @var ProductSegmentProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productSegmentProvider;

    /**
     * @var ProductManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productManager;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->productSegmentProvider = $this->createMock(ProductSegmentProviderInterface::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->cache = $this->createMock(Cache::class);

        $this->provider = new FeaturedProductsProvider(
            $this->segmentManager,
            $this->productSegmentProvider,
            $this->productManager,
            $this->configManager,
            $this->cache
        );
    }

    public function testGetAll()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID))
            ->willReturn(1);

        $segment = new Segment();
        $segment->setEntity(Product::class);

        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $this->segmentManager
            ->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn($qb);

        $query = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn(['result']);

        $restrictionQB = $this->createMock(QueryBuilder::class);
        $restrictionQB->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->productManager
            ->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($qb, [])
            ->willReturn($restrictionQB);

        $this->assertEquals(['result'], $this->provider->getAll());
    }

    public function testGetAllWithoutConfig()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID))
            ->willReturn(null);

        $this->productSegmentProvider
            ->expects($this->never())
            ->method('getProductSegmentById');

        $this->segmentManager
            ->expects($this->never())
            ->method('getEntityQueryBuilder');

        $this->assertEquals([], $this->provider->getAll());
    }

    public function testGetCollectionWithoutSegment()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID))
            ->willReturn(1);

        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn(null);

        $this->segmentManager
            ->expects($this->never())
            ->method('getEntityQueryBuilder');

        $this->assertEquals([], $this->provider->getAll());
    }

    public function testGetAllWithoutQueryBuilder()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID))
            ->willReturn(1);

        $segment = new Segment();
        $segment->setEntity(Product::class);

        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $this->segmentManager
            ->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn(null);

        $this->productManager
            ->expects($this->never())
            ->method('restrictQueryBuilder');

        $this->assertEquals([], $this->provider->getAll());
    }
}
