<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener\FrontendLineItemsGrid;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsDataOnResultAfterListener;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LineItemsDataOnResultAfterListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var LineItemsDataOnResultAfterListener */
    private $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);

        $this->listener = new LineItemsDataOnResultAfterListener($this->eventDispatcher, $this->entityClassResolver);
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [],
            $this->createMock(AbstractQuery::class)
        );

        $this->listener->onResultAfter($event);

        $this->assertCount(0, $event->getRecords());
    }

    public function testOnResultAfterWhenNoLineItemsIds(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagridConfig = $this->createMock(DatagridConfiguration::class);
        $datagrid
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $ormQueryConfig = $this->createMock(OrmQueryConfiguration::class);
        $datagridConfig
            ->expects($this->any())
            ->method('getOrmQuery')
            ->willReturn($ormQueryConfig);

        $ormQueryConfig
            ->expects($this->any())
            ->method('getRootEntity')
            ->with($this->entityClassResolver)
            ->willReturn(ProductLineItemInterface::class);

        $this->listener->onResultAfter(
            new OrmResultAfter(
                $datagrid,
                [new ResultRecord([]), new ResultRecord(['allLineItemsIds' => '']), new ResultRecord(['id' => ''])],
                $this->createMock(AbstractQuery::class)
            )
        );
    }

    public function testOnResultAfterWhenNotProductLineItemInterface(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagridConfig = $this->createMock(DatagridConfiguration::class);
        $datagrid
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $ormQueryConfig = $this->createMock(OrmQueryConfiguration::class);
        $datagridConfig
            ->expects($this->any())
            ->method('getOrmQuery')
            ->willReturn($ormQueryConfig);

        $ormQueryConfig
            ->expects($this->any())
            ->method('getRootEntity')
            ->with($this->entityClassResolver)
            ->willReturn(\stdClass::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('An entity with interface %s was expected, got stdClass', ProductLineItemInterface::class)
        );

        $this->listener->onResultAfter(
            new OrmResultAfter(
                $datagrid,
                [
                    new ResultRecord(['allLineItemsIds' => '1001']),
                    new ResultRecord(['id' => '2002']),
                ],
                $this->createMock(AbstractQuery::class)
            )
        );
    }

    /**
     * @dataProvider onResultAfterDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnResultAfter(array $datagridParameters, bool $isGrouped): void
    {
        $lineItems = [
            1001 => new ProductLineItem(1001),
            2002 => new ProductLineItem(2002),
            3003 => new ProductLineItem(3003),
        ];

        $lineItemsData = [
            1001 => ['sample_key' => 'sample_value1'],
            2002 => ['sample_key' => 'sample_value2'],
            3003 => ['sample_key' => 'sample_value3'],
        ];

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects($this->any())
            ->method('getName')
            ->willReturn('test-grid');

        $datagrid
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag($datagridParameters));

        $datagridConfig = $this->createMock(DatagridConfiguration::class);
        $datagrid
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $ormQueryConfig = $this->createMock(OrmQueryConfiguration::class);
        $datagridConfig
            ->expects($this->any())
            ->method('getOrmQuery')
            ->willReturn($ormQueryConfig);

        $ormQueryConfig
            ->expects($this->any())
            ->method('getRootEntity')
            ->with($this->entityClassResolver)
            ->willReturn(ProductLineItem::class);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (
                    DatagridLineItemsDataEvent $datagridLineItemsDataEvent,
                    string $name
                ) use (
                    $datagrid,
                    $lineItems,
                    $lineItemsData,
                    $isGrouped
                ) {
                    $this->assertSame($lineItems, $datagridLineItemsDataEvent->getLineItems());
                    $this->assertSame($datagrid, $datagridLineItemsDataEvent->getDatagrid());
                    $this->assertEquals($datagridLineItemsDataEvent->getName(), $name);
                    $this->assertSame(['isGrouped' => $isGrouped], $datagridLineItemsDataEvent->getContext());

                    $datagridLineItemsDataEvent->addDataForLineItem(1001, $lineItemsData[1001]);
                    $datagridLineItemsDataEvent->addDataForLineItem(2002, $lineItemsData[2002]);
                    $datagridLineItemsDataEvent->addDataForLineItem(3003, $lineItemsData[3003]);

                    return $datagridLineItemsDataEvent;
                }
            );

        $query = $this->createMock(AbstractQuery::class);
        $entityManager = $this->createMock(EntityManager::class);
        $query
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager
            ->expects($this->exactly(3))
            ->method('getReference')
            ->willReturnMap(
                [
                    [ProductLineItem::class, 1001, $lineItems[1001]],
                    [ProductLineItem::class, 2002, $lineItems[2002]],
                    [ProductLineItem::class, 3003, $lineItems[3003]],
                ]
            );

        $record1Data = ['allLineItemsIds' => '1001,2002'];
        $record1 = new ResultRecord($record1Data);
        $record2Data = ['id' => '3003'];
        $record2 = new ResultRecord($record2Data);
        $this->listener->onResultAfter(
            new OrmResultAfter($datagrid, [$record1, $record2], $query)
        );

        $this->assertSame(
            [
                1001 => array_replace($record1Data, $lineItemsData[1001]),
                2002 => array_replace($record1Data, $lineItemsData[2002]),
            ],
            $record1->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA)
        );
        $this->assertSame(
            [1001 => $lineItems[1001], 2002 => $lineItems[2002]],
            $record1->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS)
        );

        $this->assertSame(
            [3003 => array_replace($record2Data, ['sample_key' => 'sample_value3'])],
            $record2->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA)
        );
        $this->assertSame(
            [3003 => $lineItems[3003]],
            $record2->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS)
        );
    }

    public function onResultAfterDataProvider(): array
    {
        return [
            'group is true' => [
                'parameters' => ['_parameters' => ['group' => true]],
                'isGrouped' => true,
            ],
            'group is false' => [
                'parameters' => ['_parameters' => ['group' => false]],
                'isGrouped' => false,
            ],
            'group is 0' => [
                'parameters' => ['_parameters' => ['group' => 0]],
                'isGrouped' => false,
            ],
            'empty parameters' => [
                'parameters' => ['_parameters' => []],
                'isGrouped' => false,
            ],
            'no parameters' => [
                'parameters' => [],
                'isGrouped' => false,
            ],
        ];
    }
}
