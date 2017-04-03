<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class FeaturedProductsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FeaturedProductsProvider */
    private $provider;

    /** @var SegmentManager|\PHPUnit_Framework_MockObject_MockObject */
    private $segmentManager;

    /** @var ProductManager|\PHPUnit_Framework_MockObject_MockObject */
    private $productManager;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new FeaturedProductsProvider(
            $this->segmentManager,
            $this->productManager,
            $this->configManager,
            $this->logger
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

        $this->segmentManager
            ->expects($this->once())
            ->method('findById')
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

        $this->segmentManager
            ->expects($this->never())
            ->method('findById');

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

        $this->segmentManager
            ->expects($this->once())
            ->method('findById')
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

        $this->segmentManager
            ->expects($this->once())
            ->method('findById')
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

    public function testGetAllWithWrongEntity()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID))
            ->willReturn(1);

        $segmentType = new SegmentType(SegmentType::TYPE_DYNAMIC);
        $segment = new Segment();
        $segment->setEntity(\stdClass::class);
        $segment->setType($segmentType);

        $this->segmentManager
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->segmentManager
            ->expects($this->never())
            ->method('getEntityQueryBuilder');

        $this->productManager
            ->expects($this->never())
            ->method('restrictQueryBuilder');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                sprintf('Expected "%s", but "%s" is given.', Product::class, $segment->getEntity()),
                [
                    'id' => null,
                    'name' => null,
                    'entity' => $segment->getEntity(),
                    'type' => SegmentType::TYPE_DYNAMIC,
                ]
            );

        $this->assertEquals([], $this->provider->getAll());
    }
}
