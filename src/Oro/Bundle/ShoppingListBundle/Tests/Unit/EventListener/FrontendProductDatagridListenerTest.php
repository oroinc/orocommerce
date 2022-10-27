<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener;
use Oro\Component\Testing\ReflectionUtil;

class FrontendProductDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductShoppingListsDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productShoppingListsDataProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FrontendProductDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->productShoppingListsDataProvider = $this->createMock(ProductShoppingListsDataProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new FrontendProductDatagridListener(
            $this->productShoppingListsDataProvider,
            $this->doctrine
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    public function testOnPreBuild()
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);

        $this->listener->onPreBuild(new PreBuild($config, new ParameterBag()));

        $this->assertEquals(
            [
                'name'       => 'grid-name',
                'properties' => [
                    'shopping_lists' => [
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
        $datagrid = $this->createMock(DatagridInterface::class);

        $record = new ResultRecord(['id' => 777]);
        $product = $this->getProduct(777);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('getReference')
            ->with(Product::class, 777)
            ->willReturn($product);

        $this->productShoppingListsDataProvider->expects($this->once())
            ->method('getProductsUnitsQuantity')
            ->with([$product])
            ->willReturn([]);

        $query = $this->createMock(SearchQueryInterface::class);

        $this->listener->onResultAfter(new SearchResultAfter($datagrid, $query, [$record]));

        $this->assertNull($record->getValue('shopping_lists'));
    }

    public function testOnResultAfterNoRecords()
    {
        $datagrid = $this->createMock(DatagridInterface::class);

        $this->productShoppingListsDataProvider->expects($this->once())
            ->method('getProductsUnitsQuantity')
            ->with([])
            ->willReturn([]);

        $query = $this->createMock(SearchQueryInterface::class);

        $this->listener->onResultAfter(new SearchResultAfter($datagrid, $query, []));
    }

    public function testOnResultAfter()
    {
        $datagrid = $this->createMock(DatagridInterface::class);

        $record1 = new ResultRecord(['id' => 777]);
        $record2 = new ResultRecord(['id' => 555]);
        $record3 = new ResultRecord(['id' => 444]);

        $product777 = $this->getProduct(777);
        $product555 = $this->getProduct(555);
        $product444 = $this->getProduct(444);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);

        $em->expects($this->any())
            ->method('getReference')
            ->withConsecutive([Product::class, 777], [Product::class, 555], [Product::class, 444])
            ->willReturnOnConsecutiveCalls($product777, $product555, $product444);

        $this->productShoppingListsDataProvider->expects($this->once())
            ->method('getProductsUnitsQuantity')
            ->with([$product777, $product555, $product444])
            ->willReturn([
                777 => ['Some data'],
                555 => ['Some data2'],
            ]);

        $query = $this->createMock(SearchQueryInterface::class);

        $this->listener->onResultAfter(new SearchResultAfter($datagrid, $query, [$record1, $record2, $record3]));

        $this->assertEquals(['Some data'], $record1->getValue('shopping_lists'));
        $this->assertEquals(['Some data2'], $record2->getValue('shopping_lists'));
        $this->assertNull($record3->getValue('shopping_lists'));
    }
}
