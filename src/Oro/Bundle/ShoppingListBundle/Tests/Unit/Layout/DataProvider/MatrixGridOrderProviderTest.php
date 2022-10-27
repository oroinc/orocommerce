<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Component\Testing\ReflectionUtil;

class MatrixGridOrderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MatrixGridOrderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $matrixGridManager;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProvider;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var MatrixGridOrderProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->matrixGridManager = $this->createMock(MatrixGridOrderManager::class);
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new MatrixGridOrderProvider(
            $this->matrixGridManager,
            $this->totalProvider,
            $this->numberFormatter,
            $this->currentShoppingListManager,
            $this->doctrine
        );
    }

    private function getProduct(int $id, string $type = Product::TYPE_SIMPLE): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);
        $product->setType($type);

        return $product;
    }

    private function getProductView(int $id, string $type = Product::TYPE_SIMPLE): ProductView
    {
        $product = new ProductView();
        $product->set('id', $id);
        $product->set('type', $type);

        return $product;
    }

    private function getProductUnit(): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode('items');

        return $unit;
    }

    private function getCustomerUser(): CustomerUser
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 500);

        return $customerUser;
    }

    private function getShoppingList(int $id = null): ShoppingList
    {
        $shoppingList = new ShoppingList();
        if (null !== $id) {
            ReflectionUtil::setId($shoppingList, $id);
        }

        return $shoppingList;
    }

    private function getLineItem(
        int $id = null,
        Product $product = null,
        ProductUnit $unit = null,
        int $quantity = null
    ): LineItem {
        $lineItem = new LineItem();
        if (null !== $id) {
            ReflectionUtil::setId($lineItem, $id);
        }
        if (null !== $product) {
            $lineItem->setProduct($product);
        }
        if (null !== $unit) {
            $lineItem->setUnit($unit);
        }
        if (null !== $quantity) {
            $lineItem->setQuantity($quantity);
        }

        return $lineItem;
    }

    public function testCalculateTotalQuantityWithShoppingList()
    {
        $product = $this->getProduct(1);

        $shoppingList = $this->getShoppingList(1000);

        $collection = $this->createCollection();
        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[0]->columns[1]->quantity = 3;

        $this->currentShoppingListManager->expects($this->never())
            ->method('getCurrent');

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product, $shoppingList)
            ->willReturn($collection);

        $this->assertEquals(8, $this->provider->getTotalQuantity($product, $shoppingList));
    }

    public function testCalculateTotalQuantityWithDefaultShoppingList()
    {
        $product = $this->getProduct(1);

        $shoppingList = $this->getShoppingList(1000);

        $collection = $this->createCollection();
        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[0]->columns[1]->quantity = 3;

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product, $shoppingList)
            ->willReturn($collection);

        $this->assertEquals(8, $this->provider->getTotalQuantity($product));
    }

    public function testCalculateTotalPriceWithShoppingList()
    {
        $product = $this->getProduct(1, Product::TYPE_CONFIGURABLE);

        $simpleProduct00 = $this->getProduct(2);
        $simpleProduct10 = $this->getProduct(3);

        $productUnit = $this->getProductUnit();

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[0]->columns[0]->product = $simpleProduct00;

        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

        $customerUser = $this->getCustomerUser();

        $shoppingList = $this->getShoppingList(1000);
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->addLineItem($this->getLineItem(10001));

        $this->currentShoppingListManager->expects($this->never())
            ->method('getCurrent');

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product, $shoppingList)
            ->willReturn($collection);

        $tempShoppingList = $this->getShoppingList();
        $tempShoppingList->setCustomerUser($customerUser);
        $tempShoppingList->addLineItem($this->getLineItem(null, $simpleProduct00, $productUnit, 1));
        $tempShoppingList->addLineItem($this->getLineItem(null, $simpleProduct10, $productUnit, 4));

        $subtotal = new Subtotal();
        $subtotal->setAmount(5);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($tempShoppingList)
            ->willReturn($subtotal);

        $formattedCurrency = 'formatted currency';
        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(5)
            ->willReturn($formattedCurrency);

        $this->assertEquals($formattedCurrency, $this->provider->getTotalPriceFormatted($product, $shoppingList));
    }

    public function testCalculateTotalPriceWithDefaultShoppingList()
    {
        $product = $this->getProduct(1, Product::TYPE_CONFIGURABLE);

        $simpleProduct00 = $this->getProduct(2);
        $simpleProduct10 = $this->getProduct(3);

        $productUnit = $this->getProductUnit();

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[0]->columns[0]->product = $simpleProduct00;

        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

        $customerUser = $this->getCustomerUser();

        $shoppingList = $this->getShoppingList(1000);
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->addLineItem($this->getLineItem(10001));

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $tempShoppingList = $this->getShoppingList();
        $tempShoppingList->setCustomerUser($customerUser);
        $tempShoppingList->addLineItem($this->getLineItem(null, $simpleProduct00, $productUnit, 1));
        $tempShoppingList->addLineItem($this->getLineItem(null, $simpleProduct10, $productUnit, 4));

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product, $shoppingList)
            ->willReturn($collection);

        $subtotal = new Subtotal();
        $subtotal->setAmount(5);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($tempShoppingList)
            ->willReturn($subtotal);

        $formattedCurrency = 'formatted currency';
        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(5)
            ->willReturn($formattedCurrency);

        $this->assertEquals($formattedCurrency, $this->provider->getTotalPriceFormatted($product));
    }

    public function testCalculateTotalPriceInit()
    {
        $product = $this->getProduct(1, Product::TYPE_CONFIGURABLE);

        $simpleProduct00 = $this->getProduct(2);
        $simpleProduct10 = $this->getProduct(3);
        $simpleProduct11 = $this->getProduct(4);

        $productUnit = $this->getProductUnit();

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->product = $simpleProduct00;
        $collection->rows[0]->columns[0]->quantity = 2;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;
        $collection->rows[1]->columns[0]->quantity = 1;
        $collection->rows[1]->columns[1]->product = $simpleProduct11;
        $collection->rows[1]->columns[1]->quantity = 0;

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product)
            ->willReturn($collection);

        $tempShoppingList = $this->getShoppingList();
        $tempShoppingList->addLineItem($this->getLineItem(null, $simpleProduct00, $productUnit, 2));
        $tempShoppingList->addLineItem($this->getLineItem(null, $simpleProduct10, $productUnit, 1));

        $subtotal = new Subtotal();
        $subtotal->setAmount(0);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($tempShoppingList)
            ->willReturn($subtotal);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn(null);

        $formattedCurrency = 'formatted currency';
        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(0)
            ->willReturn($formattedCurrency);

        $this->assertEquals($formattedCurrency, $this->provider->getTotalPriceFormatted($product));
    }

    private function createCollection(): MatrixCollection
    {
        $column00 = new MatrixCollectionColumn();
        $column10 = new MatrixCollectionColumn();
        $column01 = new MatrixCollectionColumn();
        $column11 = new MatrixCollectionColumn();

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->columns = [$column00, $column10];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->columns = [$column01, $column11];

        $collection = new MatrixCollection();
        $collection->rows = [$rowSmall, $rowMedium];

        return $collection;
    }

    private function prepareDataForGetTotalsQuantityPrice(): array
    {
        $configurableProduct100 = $this->getProduct(100, Product::TYPE_CONFIGURABLE);
        $collection1 = $this->createCollection();
        $collection1->rows[0]->columns[0]->quantity = 1;
        $collection1->rows[1]->columns[0]->quantity = 4;
        $collection1->rows[0]->columns[1]->quantity = 3;

        $configurableProduct200 = $this->getProduct(200, Product::TYPE_CONFIGURABLE);
        $collection2 = $this->createCollection();
        $collection2->rows[0]->columns[0]->quantity = 2;
        $collection2->rows[1]->columns[0]->quantity = 5;

        $configurableProduct300 = $this->getProduct(300, Product::TYPE_CONFIGURABLE);
        $collection3 = $this->createCollection();
        $collection3->rows[0]->columns[0]->quantity = 5;
        $collection3->rows[1]->columns[0]->quantity = 1;
        $collection3->rows[0]->columns[1]->quantity = 0;
        $collection3->rows[1]->columns[1]->quantity = 3;

        $simpleProduct1 = $this->getProduct(1);

        $products = [
            $this->getProductView($configurableProduct100->getId(), $configurableProduct100->getType()),
            $this->getProductView($configurableProduct200->getId(), $configurableProduct200->getType()),
            $this->getProductView($simpleProduct1->getId(), $simpleProduct1->getType()),
            $this->getProductView($configurableProduct300->getId(), $configurableProduct300->getType())
        ];
        $collections = [
            $configurableProduct100->getId() => $collection1,
            $configurableProduct200->getId() => $collection2,
            $configurableProduct300->getId() => $collection3,
        ];

        $productReferences = [
            $configurableProduct100,
            $configurableProduct200,
            $configurableProduct300,
            $simpleProduct1
        ];
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects($this->any())
            ->method('getReference')
            ->with(Product::class)
            ->willReturnCallback(function (string $entityClass, int $id) use ($productReferences) {
                foreach ($productReferences as $productReference) {
                    if ($productReference->getId() === $id) {
                        return $productReference;
                    }
                }
                throw new \InvalidArgumentException(sprintf('Unknown product %d.', $id));
            });

        $this->matrixGridManager->expects($this->any())
            ->method('getMatrixCollection')
            ->willReturnCallback(function (Product $product) use ($collections) {
                return $collections[$product->getId()];
            });

        $subtotal = new Subtotal();

        $this->totalProvider->expects($this->any())
            ->method('getTotal')
            ->willReturn($subtotal);

        $this->numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnOnConsecutiveCalls(
                'USD 125',
                '55,40 EUR',
                '100 EUR'
            );

        return [
            'products' => $products,
            'expected' => [
                100 => [
                    'quantity' => 8,
                    'price' => 'USD 125',
                ],
                200 => [
                    'quantity' => 7,
                    'price' => '55,40 EUR',
                ],
                300 => [
                    'quantity' => 9,
                    'price' => '100 EUR',
                ]
            ]
        ];
    }

    public function testGetTotalsQuantityPriceWithDefaultShoppingList()
    {
        $shoppingList = $this->getShoppingList(1000);

        $this->currentShoppingListManager->expects($this->exactly(6))
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $preparedData = $this->prepareDataForGetTotalsQuantityPrice();

        $this->assertEquals(
            $preparedData['expected'],
            $this->provider->getTotalsQuantityPrice($preparedData['products'])
        );
    }

    public function testGetTotalsQuantityPriceWithShoppingList()
    {
        $shoppingList = $this->getShoppingList(1000);

        $this->currentShoppingListManager->expects($this->never())
            ->method('getCurrent');

        $preparedData = $this->prepareDataForGetTotalsQuantityPrice();

        $this->assertEquals(
            $preparedData['expected'],
            $this->provider->getTotalsQuantityPrice($preparedData['products'], $shoppingList)
        );
    }
}
