<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ProductShoppingListsDataProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productShoppingListsDataProvider;

    /**
     * @var FrontendProductDatagridListener
     */
    private $listener;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->productShoppingListsDataProvider = $this->getMockBuilder(ProductShoppingListsDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $this->listener = new FrontendProductDatagridListener(
            $this->productShoppingListsDataProvider,
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
                    FrontendProductDatagridListener::COLUMN_LINE_ITEMS => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                    ]
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfterNoShoppingList()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $record = new ResultRecord(['id' => 777]);
        $product = $this->getEntity(Product::class, ['id' => 777]);

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(Product::class, 777)
            ->willReturn($product);

        $this->productShoppingListsDataProvider
            ->expects($this->once())
            ->method('getProductsUnitsQuantity')
            ->with([$product])
            ->willReturn([]);

        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, [$record]);

        $this->listener->onResultAfter($event);

        $this->assertNull($record->getValue('shopping_lists'));
    }

    public function testOnResultAfterNoRecords()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $this->productShoppingListsDataProvider
            ->expects($this->once())
            ->method('getProductsUnitsQuantity')
            ->with([])
            ->willReturn([]);
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

        $record1 = new ResultRecord(['id' => 777]);
        $record2 = new ResultRecord(['id' => 555]);
        $record3 = new ResultRecord(['id' => 444]);

        $product777 = $this->getEntity(Product::class, ['id' => 777]);
        $product555 = $this->getEntity(Product::class, ['id' => 555]);
        $product444 = $this->getEntity(Product::class, ['id' => 444]);

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->any())
            ->method('getReference')
            ->withConsecutive([Product::class, 777], [Product::class, 555], [Product::class, 444])
            ->willReturnOnConsecutiveCalls($product777, $product555, $product444);

        $this->productShoppingListsDataProvider
            ->expects($this->once())
            ->method('getProductsUnitsQuantity')
            ->with([$product777, $product555, $product444])
            ->willReturn([
                777 => ['Some data'],
                555 => ['Some data2'],
            ]);
        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, [$record1, $record2, $record3]);

        $this->listener->onResultAfter($event);

        $this->assertEquals(['Some data'], $record1->getValue('shopping_lists'));
        $this->assertEquals(['Some data2'], $record2->getValue('shopping_lists'));
        $this->assertNull($record3->getValue('shopping_lists'));
    }
}
