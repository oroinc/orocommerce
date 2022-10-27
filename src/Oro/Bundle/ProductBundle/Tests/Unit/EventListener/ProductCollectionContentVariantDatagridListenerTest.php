<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionContentVariantDatagridListener;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionDatagridListener;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductCollectionContentVariantDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @var NameStrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $nameStrategy;

    /**
     * @var ProductCollectionContentVariantDatagridListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->nameStrategy = $this->createMock(NameStrategyInterface::class);

        $this->listener = new ProductCollectionContentVariantDatagridListener(
            $this->requestStack,
            $this->nameStrategy,
        );
    }

    public function testOnBuildWhenSegmentGridParamsSet()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $andX = new Andx();
        $expr->expects($this->once())
            ->method('eq')
            ->with('collectionSortOrder.product', 'product.id')
            ->willReturn('collectionSortOrder.product = product.id');
        $expr->expects($this->once())
            ->method('andX')
            ->willReturn($andX);
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('addSelect')
            ->with('collectionSortOrder.sortOrder as categorySortOrder')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('leftJoin')
            ->with(
                CollectionSortOrder::class,
                'collectionSortOrder',
                Join::WITH,
                $andX
            )
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        /** @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid
            ->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $parameters = new ParameterBag([
            'params' => [
                'segmentId' => '1',
                'segmentDefinition' => '{}',
                'includedProducts' => '1,2',
                'excludedProducts' => '5',
            ]
        ]);

        $dataGrid
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $event = new BuildAfter($dataGrid);

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithoutRequest()
    {
        /** @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterNotOrmDatasource()
    {
        $dataSource = $this->createMock(DatasourceInterface::class);

        /** @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $dataGrid
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithEmptyRequestData()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $andX = new Andx();
        $expr->expects($this->once())
            ->method('eq')
            ->with('collectionSortOrder.product', 'product.id')
            ->willReturn('collectionSortOrder.product = product.id');
        $expr->expects($this->once())
            ->method('andX')
            ->willReturn($andX);
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('addSelect')
            ->with('collectionSortOrder.sortOrder as categorySortOrder')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('leftJoin')
            ->with(
                CollectionSortOrder::class,
                'collectionSortOrder',
                Join::WITH,
                $andX
            )
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        /** @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $dataGrid
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->assertGetGridFullNameCalls($dataGrid, 'grid_name', '1');

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());

        $this->listener->onBuildAfter($event);
    }

    /**
     * @dataProvider gridNameDataProvider
     * @param string $gridName
     * @param string $scope
     * @param string $resolvedName
     */
    public function testOnBuildAfterWhenDefinitionFromRequest($gridName, $scope, $resolvedName)
    {
        $segmentType = new SegmentType(SegmentType::TYPE_DYNAMIC);

        $segmentDefinition = 'definition';
        $updatedSegmentDefinition = '{"filters":["merged-filters"]}';
        $included = '1,2';
        $excluded = '3,4';
        $qb = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $andX = new Andx();
        $expr->expects($this->once())
            ->method('eq')
            ->with('collectionSortOrder.product', 'product.id')
            ->willReturn('collectionSortOrder.product = product.id');
        $expr->expects($this->once())
            ->method('andX')
            ->willReturn($andX);
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('addSelect')
            ->with('collectionSortOrder.sortOrder as categorySortOrder')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('leftJoin')
            ->with(
                CollectionSortOrder::class,
                'collectionSortOrder',
                Join::WITH,
                $andX
            )
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        /** @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $dataGrid
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->assertGetGridFullNameCalls($dataGrid, $gridName, $scope);
        $event = new BuildAfter($dataGrid);

        $requestParameterKey = ProductCollectionDatagridListener::SEGMENT_DEFINITION_PARAMETER_KEY . $resolvedName;
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([
                $requestParameterKey => $segmentDefinition,
                $requestParameterKey . ':incl' => $included,
                $requestParameterKey . ':excl' => $excluded
            ]));

        $createdSegment = new Segment();
        $createdSegment->setDefinition($updatedSegmentDefinition)
            ->setEntity(Product::class)
            ->setType($segmentType);

        $this->listener->onBuildAfter($event);
    }

    public function gridNameDataProvider(): array
    {
        return [
            'without scope' => ['grid_name', null, 'grid_name'],
            'with 0 scope' => ['grid_name', '0', 'grid_name:0'],
            'with scope' => ['grid_name', '1', 'grid_name:1']
        ];
    }

    /**
     * @param Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid
     * @param string $gridName
     * @param string $gridScope
     */
    private function assertGetGridFullNameCalls(Datagrid $dataGrid, $gridName, $gridScope)
    {
        $gridFullName = $gridName;
        if ($gridScope !== null) {
            $gridFullName .= ':' . $gridScope;
        }
        $dataGrid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);
        $dataGrid->expects($this->once())
            ->method('getScope')
            ->willReturn($gridScope);
        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->with($gridName, $gridScope)
            ->willReturn($gridFullName);
    }
}
