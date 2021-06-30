<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductDatagridLowInventoryListener;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductDatagridLowInventoryListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LowInventoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lowInventoryProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductDatagridLowInventoryListener */
    private $listener;

    protected function setUp(): void
    {
        $this->lowInventoryProvider = $this->createMock(LowInventoryProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new ProductDatagridLowInventoryListener(
            $this->lowInventoryProvider,
            $this->doctrineHelper
        );
    }

    public function testOnPreBuild()
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event  = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name'       => 'grid-name',
                'properties' => [
                    ProductDatagridLowInventoryListener::COLUMN_LOW_INVENTORY => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_BOOLEAN
                    ]
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfterNoLowInventory()
    {
        $dataGrid = $this->createMock(DatagridInterface::class);

        $product1 = $this->getProductEntity(777);

        $record = new ResultRecord(['id' => $product1->getId()]);

        $products = [
            [
                'product' => $product1,
            ]
        ];

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$product1]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($productRepository);

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with($products)
            ->willReturn([]);

        $query = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultAfter($dataGrid, $query, [$record]);

        $this->listener->onResultAfter($event);

        $this->assertNull($record->getValue('low_inventory'));
    }

    public function testOnResultAfterNoRecords()
    {
        $datagrid = $this->createMock(DatagridInterface::class);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($productRepository);

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with([])
            ->willReturn([]);

        $query = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultAfter($datagrid, $query, []);

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfter()
    {
        $datagrid = $this->createMock(DatagridInterface::class);

        $product1 = $this->getProductEntity(777);
        $product2 = $this->getProductEntity(555);
        $product3 = $this->getProductEntity(444);
        $productWithoutPrimaryUnitPrecision = $this->getProductEntity(333, false);

        $record1 = new ResultRecord(['id' => $product1->getId()]);
        $record2 = new ResultRecord(['id' => $product2->getId()]);
        $record3 = new ResultRecord(['id' => $product3->getId()]);
        $recordWithoutPrimaryUnitPrecision = new ResultRecord(['id' => $productWithoutPrimaryUnitPrecision->getId()]);

        $preparedRecords = [
            [
                'product' => $product1,
            ],
            [
                'product' => $product2,
            ],
            [
                'product' => $product3,
            ],
            [
                'product' => $productWithoutPrimaryUnitPrecision,
            ]
        ];

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([
                $product1,
                $product2,
                $product3,
                $productWithoutPrimaryUnitPrecision
            ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($productRepository);

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with($preparedRecords)
            ->willReturn([
                777 => true,
                555 => false
            ]);

        $query = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultAfter(
            $datagrid,
            $query,
            [
                $record1,
                $record2,
                $record3,
                $recordWithoutPrimaryUnitPrecision
            ]
        );

        $this->listener->onResultAfter($event);

        $this->assertEquals(true, $record1->getValue('low_inventory'));
        $this->assertEquals(false, $record2->getValue('low_inventory'));
        $this->assertEquals(false, $record3->getValue('low_inventory'));
        $this->assertEquals(false, $recordWithoutPrimaryUnitPrecision->getValue('low_inventory'));
    }

    private function getProductEntity(int $id, bool $withPrimaryUnitPrecision = true): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        $unitPrecision = new ProductUnitPrecision();
        $unit = new ProductUnit();
        $unitPrecision->setUnit($unit);

        if ($withPrimaryUnitPrecision) {
            $product->setPrimaryUnitPrecision($unitPrecision);
        }

        return $product;
    }
}
