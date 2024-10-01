<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\AddProductsMassAction;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\AddProductsMassActionHandler;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\ReflectionUtil;

class AddProductsMassActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const MESSAGE = 'test message';

    /** @var ShoppingListLineItemHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLineItemHandler;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductShoppingListsDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productShoppingListsDataProvider;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var AddProductsMassActionHandler */
    private $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->shoppingListLineItemHandler = $this->createMock(ShoppingListLineItemHandler::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->productShoppingListsDataProvider = $this->createMock(ProductShoppingListsDataProvider::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $messageGenerator = $this->createMock(MessageGenerator::class);
        $messageGenerator->expects($this->any())
            ->method('getSuccessMessage')
            ->willReturn(self::MESSAGE);

        $this->handler = new AddProductsMassActionHandler(
            $this->shoppingListLineItemHandler,
            $messageGenerator,
            $this->doctrine,
            $this->productShoppingListsDataProvider,
            $this->aclHelper
        );
    }

    public function testHandleMissingShoppingList()
    {
        $args = $this->getMassActionArgs([]);

        $this->shoppingListLineItemHandler->expects($this->never())
            ->method('isAllowed');

        $response = $this->handler->handle($args);

        $this->assertFalse($response->isSuccessful());
        $responseOptions = $response->getOptions();
        $this->assertEquals(0, $responseOptions['count']);
        $this->assertEquals([], $responseOptions['products']);
    }

    public function testHandleNullShoppingList()
    {
        $args = $this->getMassActionArgs(['shoppingList' => null]);

        $this->shoppingListLineItemHandler->expects($this->never())
            ->method('isAllowed');

        $response = $this->handler->handle($args);

        $this->assertFalse($response->isSuccessful());
        $responseOptions = $response->getOptions();
        $this->assertEquals(0, $responseOptions['count']);
        $this->assertEquals([], $responseOptions['products']);
    }

    public function testHandleMissingProductIds()
    {
        $shoppingList = $this->getShoppingList(1);

        $args = $this->getMassActionArgs(['shoppingList' => $shoppingList]);

        $this->shoppingListLineItemHandler->expects($this->never())
            ->method('isAllowed');

        $response = $this->handler->handle($args);
        $this->assertFalse($response->isSuccessful());
        $responseOptions = $response->getOptions();
        $this->assertEquals(0, $responseOptions['count']);
        $this->assertEquals([], $responseOptions['products']);
    }

    public function testHandleEmptyProductIds()
    {
        $shoppingList = $this->getShoppingList(1);

        $args = $this->getMassActionArgs(['shoppingList' => $shoppingList, 'values' => '']);

        $this->shoppingListLineItemHandler->expects($this->never())
            ->method('isAllowed');

        $response = $this->handler->handle($args);
        $this->assertFalse($response->isSuccessful());
        $responseOptions = $response->getOptions();
        $this->assertEquals(0, $responseOptions['count']);
        $this->assertEquals([], $responseOptions['products']);
    }

    public function testHandleNotAllowed()
    {
        $shoppingList = $this->getShoppingList(1);

        $args = $this->getMassActionArgs(['shoppingList' => $shoppingList, 'values' => '3']);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $response = $this->handler->handle($args);
        $this->assertFalse($response->isSuccessful());
        $responseOptions = $response->getOptions();
        $this->assertEquals(0, $responseOptions['count']);
        $this->assertEquals([], $responseOptions['products']);
    }

    public function testHandleCreateForShoppingListException()
    {
        $shoppingList = $this->getShoppingList(1);

        $args = $this->getMassActionArgs(['shoppingList' => $shoppingList, 'values' => '3']);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->never())
            ->method('persist');
        $em->expects($this->once())
            ->method('rollback');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('createForShoppingList')
            ->willThrowException(new \Exception());

        $this->expectException(\Exception::class);

        $this->handler->handle($args);
    }

    public function testHandleExistingShoppingList()
    {
        $shoppingList = $this->getShoppingList(1);
        $productUnitsWithQuantities = [
            23 => ['item' => 1.0],
            42 => ['item' => 1.0],
            56 => ['item' => 1.0],
        ];

        $args = $this->getMassActionArgs([
            'shoppingList' => $shoppingList,
            'values' => '23,42,56',
            'units_and_quantities' => json_encode($productUnitsWithQuantities, JSON_THROW_ON_ERROR)
        ]);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('createForShoppingList')
            ->with(self::identicalTo($shoppingList), [23, 42, 56], $productUnitsWithQuantities)
            ->willReturn(2);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->never())
            ->method('persist');
        $em->expects($this->once())
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $productsShoppingLists = $this->expectProductsShoppingLists();

        $response = $this->handler->handle($args);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(self::MESSAGE, $response->getMessage());
        $responseOptions = $response->getOptions();
        $this->assertEquals(2, $responseOptions['count']);
        $this->assertEquals($productsShoppingLists, $responseOptions['products']);
    }

    public function testHandleNewShoppingList()
    {
        $shoppingList = new ShoppingListStub();
        $productUnitsWithQuantities = [
            23 => ['item' => 1.0],
            42 => ['item' => 1.0],
            56 => ['item' => 1.0],
        ];

        $args = $this->getMassActionArgs([
            'shoppingList' => $shoppingList,
            'values' => '23,42,56',
            'units_and_quantities' => json_encode($productUnitsWithQuantities, JSON_THROW_ON_ERROR)
        ]);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('createForShoppingList')
            ->with(self::identicalTo($shoppingList), [23, 42, 56], $productUnitsWithQuantities)
            ->willReturn(2);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($shoppingList);
        $em->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use ($shoppingList) {
                $shoppingList->setId(5);
            });
        $em->expects($this->once())
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $productsShoppingLists = $this->expectProductsShoppingLists();

        $response = $this->handler->handle($args);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(self::MESSAGE, $response->getMessage());
        $responseOptions = $response->getOptions();
        $this->assertEquals(2, $responseOptions['count']);
        $this->assertEquals($productsShoppingLists, $responseOptions['products']);
    }

    public function testHandleWhenNoProductUnitsWithQuantities()
    {
        $shoppingList = $this->getShoppingList(1);
        $args = $this->getMassActionArgs(['shoppingList' => $shoppingList, 'values' => '23,42,56']);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->shoppingListLineItemHandler->expects($this->once())
            ->method('createForShoppingList')
            ->with(self::identicalTo($shoppingList), [23, 42, 56], [])
            ->willReturn(2);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->never())
            ->method('persist');
        $em->expects($this->once())
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $productsShoppingLists = $this->expectProductsShoppingLists();

        $response = $this->handler->handle($args);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(self::MESSAGE, $response->getMessage());
        $responseOptions = $response->getOptions();
        $this->assertEquals(2, $responseOptions['count']);
        $this->assertEquals($productsShoppingLists, $responseOptions['products']);
    }

    public function testHandleWhenAllProductsSelected()
    {
        $shoppingList = $this->getShoppingList(1);

        $args = $this->getMassActionArgs(['shoppingList' => $shoppingList, 'values' => '3', 'inset' => '0']);

        $this->shoppingListLineItemHandler->expects($this->never())
            ->method('createForShoppingList');

        $response = $this->handler->handle($args);
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(self::MESSAGE, $response->getMessage());
        $responseOptions = $response->getOptions();
        $this->assertEquals(0, $responseOptions['count']);
        $this->assertEquals([], $responseOptions['products']);
    }

    private function getMassActionArgs(array $data): MassActionHandlerArgs
    {
        return new MassActionHandlerArgs(
            new AddProductsMassAction(),
            $this->createMock(DatagridInterface::class),
            $this->createMock(IterableResultInterface::class),
            $data
        );
    }

    private function expectProductsShoppingLists(): array
    {
        $productRepository = $this->createMock(ProductRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($productRepository);

        $products = [
            $this->getProduct(23),
            $this->getProduct(42),
            $this->getProduct(56),
        ];

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('p.id');

        $productRepository->expects($this->once())
            ->method('getProductsQueryBuilder')
            ->with([23, 42, 56])
            ->willReturn($queryBuilder);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->productShoppingListsDataProvider->expects($this->once())
            ->method('getProductsUnitsQuantity')
            ->with($products)
            ->willReturn([
                23 => [$this->getShoppingList(1)],
                42 => [$this->getShoppingList(1), $this->getShoppingList(2)],
                56 => [$this->getShoppingList(2)],
            ]);

        return [
            23 => [
                'id' => 23,
                'shopping_lists' => [$this->getShoppingList(1)],
            ],
            42 => [
                'id' => 42,
                'shopping_lists' => [$this->getShoppingList(1), $this->getShoppingList(2)],
            ],
            56 => [
                'id' => 56,
                'shopping_lists' => [$this->getShoppingList(2)],
            ],
        ];
    }

    private function getShoppingList(int $id): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);

        return $shoppingList;
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }
}
