<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Component\Testing\Unit\EntityTrait;

class MatrixGridOrderProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var MatrixGridOrderManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $matrixGridManager;

    /**
     * @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalProvider;

    /**
     * @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $numberFormatter;

    /**
     * @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $currentShoppingListManager;

    /** @var MatrixGridOrderProvider */
    private $provider;

    protected function setUp()
    {
        $this->matrixGridManager = $this->createMock(MatrixGridOrderManager::class);
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);

        $this->provider = new MatrixGridOrderProvider(
            $this->matrixGridManager,
            $this->totalProvider,
            $this->numberFormatter,
            $this->currentShoppingListManager
        );
    }

    public function testCalculateTotalQuantityWithShoppingList()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class);

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
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class);

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
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $simpleProduct00 = $this->getEntity(Product::class);
        $simpleProduct10 = $this->getEntity(Product::class);

        $productUnit = $this->getEntity(ProductUnit::class);

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[0]->columns[0]->product = $simpleProduct00;

        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class);

        $this->currentShoppingListManager->expects($this->never())
            ->method('getCurrent');

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product, $shoppingList)
            ->willReturn($collection);

        $lineItem00 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct00,
            'unit' => $productUnit,
            'quantity' => 1
        ]);
        $lineItem10 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct10,
            'unit' => $productUnit,
            'quantity' => 4
        ]);

        $tempShoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => [$lineItem00, $lineItem10]
        ]);

        $subtotal = new Subtotal();
        $subtotal->setAmount(5);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($tempShoppingList)
            ->willReturn($subtotal);

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(5);

        $this->provider->getTotalPriceFormatted($product, $shoppingList);
    }

    public function testCalculateTotalPriceWithDefaultShoppingList()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $simpleProduct00 = $this->getEntity(Product::class);
        $simpleProduct10 = $this->getEntity(Product::class);

        $productUnit = $this->getEntity(ProductUnit::class);

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[0]->columns[0]->product = $simpleProduct00;

        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $lineItem00 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct00,
            'unit' => $productUnit,
            'quantity' => 1
        ]);
        $lineItem10 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct10,
            'unit' => $productUnit,
            'quantity' => 4
        ]);

        $tempShoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => [$lineItem00, $lineItem10]
        ]);

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

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(5);

        $this->provider->getTotalPriceFormatted($product);
    }

    public function testCalculateTotalPriceInit()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $simpleProduct00 = $this->getEntity(Product::class);
        $simpleProduct10 = $this->getEntity(Product::class);

        $productUnit = $this->getEntity(ProductUnit::class);

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->product = $simpleProduct00;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product)
            ->willReturn($collection);

        $lineItem00 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct00,
            'unit' => $productUnit,
            'quantity' => 0
        ]);
        $lineItem10 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct10,
            'unit' => $productUnit,
            'quantity' => 0
        ]);

        $shoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => [$lineItem00, $lineItem10]
        ]);

        $subtotal = new Subtotal();
        $subtotal->setAmount(0);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($shoppingList)
            ->willReturn($subtotal);

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(0);

        $this->provider->getTotalPriceFormatted($product);
    }

    /**
     * @return MatrixCollection
     */
    private function createCollection()
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

    /**
     * @return array
     */
    public function getTotalsQuantityPricePrepareData()
    {
        /** @var Product $configurableProduct100 */
        $configurableProduct100 = $this->getEntity(Product::class, [
            'id' => 100,
            'type' => Product::TYPE_CONFIGURABLE,
        ]);
        $collection1 = $this->createCollection();
        $collection1->rows[0]->columns[0]->quantity = 1;
        $collection1->rows[1]->columns[0]->quantity = 4;
        $collection1->rows[0]->columns[1]->quantity = 3;

        /** @var Product $configurableProduct200 */
        $configurableProduct200 = $this->getEntity(Product::class, [
            'id' => 200,
            'type' => Product::TYPE_CONFIGURABLE,
        ]);
        $collection2 = $this->createCollection();
        $collection2->rows[0]->columns[0]->quantity = 2;
        $collection2->rows[1]->columns[0]->quantity = 5;

        /** @var Product $configurableProduct300 */
        $configurableProduct300 = $this->getEntity(Product::class, [
            'id' => 300,
            'type' => Product::TYPE_CONFIGURABLE,
        ]);
        $collection3 = $this->createCollection();
        $collection3->rows[0]->columns[0]->quantity = 5;
        $collection3->rows[1]->columns[0]->quantity = 1;
        $collection3->rows[0]->columns[1]->quantity = 0;
        $collection3->rows[1]->columns[1]->quantity = 3;

        /** @var Product $simpleProduct1 */
        $simpleProduct1 = $this->getEntity(Product::class, [
            'id' => 1,
            'type' => Product::TYPE_SIMPLE,
        ]);

        $products = [
            $configurableProduct100,
            $configurableProduct200,
            $simpleProduct1,
            $configurableProduct300,
        ];
        $collections = [
            $configurableProduct100->getId() => $collection1,
            $configurableProduct200->getId() => $collection2,
            $configurableProduct300->getId() => $collection3,
        ];

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
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class);

        $this->currentShoppingListManager->expects($this->exactly(6))
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $preparedData = $this->getTotalsQuantityPricePrepareData();

        $this->assertEquals(
            $preparedData['expected'],
            $this->provider->getTotalsQuantityPrice($preparedData['products'])
        );
    }

    public function testGetTotalsQuantityPriceWithShoppingList()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class);

        $this->currentShoppingListManager->expects($this->never())
            ->method('getCurrent');

        $preparedData = $this->getTotalsQuantityPricePrepareData();

        $this->assertEquals(
            $preparedData['expected'],
            $this->provider->getTotalsQuantityPrice($preparedData['products'], $shoppingList)
        );
    }
}
