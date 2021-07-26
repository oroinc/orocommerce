<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductDatagridUpcomingLabelListener;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductDatagridUpcomingLabelListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productUpcomingProvider;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductDatagridUpcomingLabelListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productUpcomingProvider = $this->createMock(UpcomingProductProvider::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new ProductDatagridUpcomingLabelListener(
            $this->productUpcomingProvider,
            $this->doctrineHelper
        );
    }

    public function testOnPreBuild()
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name' => 'grid-name',
                'properties' => [
                    ProductDatagridUpcomingLabelListener::COLUMN_IS_UPCOMING => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_BOOLEAN,
                    ],
                    ProductDatagridUpcomingLabelListener::COLUMN_AVAILABLE_DATE => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_DATETIME,
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfterNoLowInventory()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);

        $product1 = $this->getProductEntity(777);

        $record = new ResultRecord(['id' => $product1->getId()]);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())->method('findBy')->willReturn([$product1]);

        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')
            ->willReturn($productRepository);

        $this->productUpcomingProvider
            ->expects($this->once())->method('isUpcoming')->with($product1)->willReturn(false);

        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = new SearchResultAfter($dataGrid, $query, [$record]);

        $this->listener->onResultAfter($event);

        $this->assertNull($record->getValue('is_upcoming'));
    }

    public function testOnResultAfterNoRecords()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())->method('findBy')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')
            ->willReturn($productRepository);

        $this->productUpcomingProvider->expects($this->never())->method('isUpcoming');

        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, []);

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfter()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $product1 = $this->getProductEntity(777);
        $product2 = $this->getProductEntity(555);
        $product3 = $this->getProductEntity(444);

        $record1 = new ResultRecord(['id' => $product1->getId()]);
        $record2 = new ResultRecord(['id' => $product2->getId()]);
        $record3 = new ResultRecord(['id' => $product3->getId()]);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())->method('findBy')
            ->willReturn([$product1, $product2, $product3,]);

        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')
            ->willReturn($productRepository);

        $this->productUpcomingProvider->expects($this->at(0))->method('isUpcoming')
            ->willReturn(true);
        $this->productUpcomingProvider->expects($this->at(1))->method('isUpcoming')
            ->willReturn(false);
        $this->productUpcomingProvider->expects($this->at(2))->method('isUpcoming')
            ->willReturn(false);

        $today = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->productUpcomingProvider->expects($this->exactly(1))->method('getAvailabilityDate')
            ->willReturn($today);

        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = new SearchResultAfter(
            $datagrid,
            $query,
            [
                $record1,
                $record2,
                $record3,
            ]
        );

        $this->listener->onResultAfter($event);

        $this->assertEquals(true, $record1->getValue('is_upcoming'));
        $this->assertEquals($today, $record1->getValue('availability_date'));
        $this->assertEquals(false, $record2->getValue('is_upcoming'));
        $this->assertEquals(false, $record3->getValue('is_upcoming'));
    }

    /**
     * @param $id
     *
     * @return ProductStub
     */
    protected function getProductEntity($id, $withPrimaryUnitPrecision = true)
    {
        $product = new ProductStub();
        $product->setId($id);

        return $product;
    }
}
