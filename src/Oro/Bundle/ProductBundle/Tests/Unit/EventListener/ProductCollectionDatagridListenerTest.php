<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionDatagridListener;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductCollectionDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var SegmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $segmentManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var NameStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $nameStrategy;

    /** @var ProductCollectionDefinitionConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $definitionConverter;

    /** @var ProductCollectionDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->nameStrategy = $this->createMock(NameStrategyInterface::class);
        $this->definitionConverter = $this->createMock(ProductCollectionDefinitionConverter::class);

        $this->listener = new ProductCollectionDatagridListener(
            $this->requestStack,
            $this->segmentManager,
            $this->registry,
            $this->nameStrategy,
            $this->definitionConverter
        );
    }

    public function testOnBuildWhenSegmentGridParamsSet()
    {
        $dataSource = $this->createMock(OrmDatasource::class);

        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $parameters = new ParameterBag([
            'params' => [
                'segmentId' => '1',
                'segmentDefinition' => '{}',
                'includedProducts' => '1,2',
                'excludedProducts' => '5'
            ]
        ]);

        $dataGrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->definitionConverter->expects($this->once())
            ->method('hasFilters')
            ->with([])
            ->willReturn(false);

        $this->segmentManager->expects($this->never())
            ->method($this->anything());

        $event = new BuildAfter($dataGrid);

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithoutRequest()
    {
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $event = new BuildAfter($dataGrid);

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

        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $dataGrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());

        $this->segmentManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithEmptyRequestData()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $dataGrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->assertGetGridFullNameCalls($dataGrid, 'grid_name', '1');

        $event = new BuildAfter($dataGrid);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());

        $this->segmentManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onBuildAfter($event);
    }

    /**
     * @dataProvider gridNameDataProvider
     */
    public function testOnBuildAfterWhenDefinitionFromRequest(string $gridName, ?string $scope, string $resolvedName)
    {
        $segmentType = new SegmentType(SegmentType::TYPE_DYNAMIC);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getReference')
            ->with(SegmentType::class, SegmentType::TYPE_DYNAMIC)
            ->willReturn($segmentType);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(SegmentType::class)
            ->willReturn($em);

        $segmentDefinition = 'definition';
        $updatedSegmentDefinition = '{"filters":["merged-filters"]}';
        $included = '1,2';
        $excluded = '3,4';
        $qb = $this->createMock(QueryBuilder::class);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $dataGrid->expects($this->once())
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
        $this->definitionConverter->expects($this->once())
            ->method('putConditionsInDefinition')
            ->with($segmentDefinition, $excluded, $included)
            ->willReturn($updatedSegmentDefinition);

        $createdSegment = new Segment();
        $createdSegment->setDefinition($updatedSegmentDefinition)
            ->setEntity(Product::class)
            ->setType($segmentType);

        $this->segmentManager->expects($this->once())
            ->method('filterBySegment')
            ->with($qb, $createdSegment);

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
     * @dataProvider definitionEmptyAndNoIncludedProductsDataProvider
     */
    public function testOnBuildAfterWhenDefinitionFilterIsEmptyAndNoIncludedProducts(array $requestData)
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request($requestData));

        $qb = $this->createMock(QueryBuilder::class);
        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
             ->method('getDatasource')
             ->willReturn($dataSource);
        $dataGrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->assertGetGridFullNameCalls($dataGrid, 'grid_name', null);
        $event = new BuildAfter($dataGrid);

        $this->segmentManager->expects($this->never())
             ->method('filterBySegment');

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('1 = 0');

        $this->listener->onBuildAfter($event);
    }

    public function definitionEmptyAndNoIncludedProductsDataProvider(): array
    {
        return [
            'empty request data' => [
                'requestData' => []
            ],
            'empty filters, no included&excluded products' => [
                'requestData' => [
                    'sd_grid_name' => '{filters:[]}',
                    'sd_grid_name:incl' => null,
                    'sd_grid_name:excl' => null
                ]
            ],
            'empty filters, no included products' => [
                'requestData' => [
                    'sd_grid_name' => '{filters:[]}',
                    'sd_grid_name:incl' => null,
                    'sd_grid_name:excl' => [1, 2]
                ]
            ],
        ];
    }

    private function assertGetGridFullNameCalls(
        Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid,
        string $gridName,
        ?string $gridScope
    ): void {
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
