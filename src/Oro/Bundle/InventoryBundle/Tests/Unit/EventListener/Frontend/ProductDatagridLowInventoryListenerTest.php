<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductDatagridLowInventoryListener;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductDatagridLowInventoryListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LowInventoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lowInventoryProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductDatagridLowInventoryListener */
    private $listener;

    protected function setUp(): void
    {
        $this->lowInventoryProvider = $this->createMock(LowInventoryProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new ProductDatagridLowInventoryListener(
            $this->lowInventoryProvider,
            $this->doctrine
        );
    }

    public function testOnPreBuild()
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name'       => 'grid-name',
                'source'     => [
                    'query' => [
                        'select' => [
                            'decimal.low_inventory_threshold as low_inventory_threshold'
                        ]
                    ]
                ],
                'properties' => [
                    'low_inventory' => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_BOOLEAN
                    ]
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfterNoRecords()
    {
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->lowInventoryProvider->expects($this->never())
            ->method('isLowInventoryCollection');

        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(SearchQueryInterface::class);
        $event = new SearchResultAfter($datagrid, $query, []);

        $this->listener->onResultAfter($event);

        $this->assertSame([], $event->getRecords());
    }

    public function testOnResultAfterWhenIsLowInventoryCollectionCallReturnsEmptyResponse()
    {
        $product1Id = 123;
        $product1 = $this->createMock(Product::class);
        $productUnit1Code = 'unit1';
        $productUnit1 = $this->createMock(ProductUnit::class);
        $product1LowInventoryThreshold = 1;
        $product1HighlightLowInventory = true;

        $record1 = new ResultRecord([
            'id'                      => $product1Id,
            'unit'                    => $productUnit1Code,
            'low_inventory_threshold' => $product1LowInventoryThreshold,
            'highlight_low_inventory' => $product1HighlightLowInventory,
        ]);

        $data = [
            [
                'product'                 => $product1,
                'product_unit'            => $productUnit1,
                'low_inventory_threshold' => $product1LowInventoryThreshold,
                'highlight_low_inventory' => $product1HighlightLowInventory,
            ]
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('getReference')
            ->willReturnMap([
                [Product::class, $product1Id, $product1],
                [ProductUnit::class, $productUnit1Code, $productUnit1]
            ]);

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with($this->identicalTo($data))
            ->willReturn([]);

        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(SearchQueryInterface::class);
        $event = new SearchResultAfter($datagrid, $query, [$record1]);

        $this->listener->onResultAfter($event);

        $this->assertNull($record1->getValue('low_inventory'));
    }

    public function testOnResultAfter()
    {
        $product1Id = 10;
        $product1 = $this->createMock(Product::class);
        $productUnit1Code = 'unit1';
        $productUnit1 = $this->createMock(ProductUnit::class);
        $product1LowInventoryThreshold = 1;

        $product2Id = 20;
        $product2 = $this->createMock(Product::class);
        $productUnit2Code = 'unit2';
        $productUnit2 = $this->createMock(ProductUnit::class);

        $record1 = new ResultRecord([
            'id'                      => $product1Id,
            'unit'                    => $productUnit1Code,
            'low_inventory_threshold' => $product1LowInventoryThreshold
        ]);
        $record2 = new ResultRecord([
            'id'                      => $product2Id,
            'unit'                    => $productUnit2Code,
            'low_inventory_threshold' => ''
        ]);

        $data = [
            [
                'product'                 => $product1,
                'product_unit'            => $productUnit1,
                'low_inventory_threshold' => $product1LowInventoryThreshold,
                'highlight_low_inventory' => true,
            ],
            [
                'product'                 => $product2,
                'product_unit'            => $productUnit2,
                'low_inventory_threshold' => -1,
                'highlight_low_inventory' => false,
            ]
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects($this->exactly(4))
            ->method('getReference')
            ->willReturnMap([
                [Product::class, $product1Id, $product1],
                [ProductUnit::class, $productUnit1Code, $productUnit1],
                [Product::class, $product2Id, $product2],
                [ProductUnit::class, $productUnit2Code, $productUnit2]
            ]);

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with($this->identicalTo($data))
            ->willReturn([$product1Id => true]);

        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(SearchQueryInterface::class);
        $event = new SearchResultAfter($datagrid, $query, [$record1, $record2]);

        $this->listener->onResultAfter($event);

        $this->assertTrue($record1->getValue('low_inventory'));
        $this->assertFalse($record2->getValue('low_inventory'));
    }
}
