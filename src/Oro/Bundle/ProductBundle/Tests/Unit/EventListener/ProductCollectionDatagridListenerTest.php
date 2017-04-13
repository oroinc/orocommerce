<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionDatagridListener;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductCollectionDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var SegmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $segmentManager;

    /**
     * @var ProductCollectionDatagridListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->segmentManager = $this->getMockBuilder(SegmentManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new ProductCollectionDatagridListener($this->requestStack, $this->segmentManager);
    }

    public function testOnBuildAfterWithoutRequest()
    {
        /** @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(BuildAfter::class);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->segmentManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterNotOrmDatasource()
    {
        $dataSource = $this->createMock(DatasourceInterface::class);

        /** @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());

        $this->segmentManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithoutDefinition()
    {
        $dataSource = $this->createMock(DatasourceInterface::class);

        /** @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());

        $this->segmentManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfter()
    {
        $segmentDefinition = 'definition';
        $qb = $this->createMock(QueryBuilder::class);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        /** @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request(['segmentDefinition' => $segmentDefinition]));

        $createdSegment = new Segment();
        $createdSegment->setDefinition($segmentDefinition)
            ->setEntity(Product::class)
            ->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $this->segmentManager->expects($this->once())
            ->method('filterBySegment')
            ->with($qb, $createdSegment);

        $this->listener->onBuildAfter($event);
    }
}
