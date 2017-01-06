<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub\ProductWithSizeAndColor;
use Oro\Component\Testing\Unit\EntityTrait;

class MatrixGridOrderManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $variantAvailability;

    /** @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $totalProvider;

    /** @var MatrixGridOrderManager */
    private $manager;

    protected function setUp()
    {
        $this->variantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);

        $this->manager = new MatrixGridOrderManager(
            $this->getPropertyAccessor(),
            $this->variantAvailability,
            $this->totalProvider
        );
    }

    public function testGetVariantFields()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->variantAvailability->expects($this->at(0))
            ->method('getVariantFieldsWithAvailability')
            ->with($product)
            ->willReturn(
                [
                    'size' => ['s' => true, 'm' => false],
                    'color' => ['red' => true],
                ]
            );

        $this->variantAvailability->expects($this->at(1))
            ->method('getAllVariantsByVariantFieldName')
            ->with('size')
            ->willReturn(
                [
                    's' => 'Small',
                    'm' => 'Medium',
                ]
            );

        $this->variantAvailability->expects($this->at(2))
            ->method('getAllVariantsByVariantFieldName')
            ->with('color')
            ->willReturn(
                [
                    'red' => 'Red',
                ]
            );

        $expectedFields = [
            [
                'name' => 'size',
                'values' => [
                    ['value' => 's', 'label' => 'Small'],
                    ['value' => 'm', 'label' => 'Medium'],
                ],
            ],
            [
                'name' => 'color',
                'values' => [
                    ['value' => 'red', 'label' => 'Red'],
                ],
            ],
        ];

        $this->assertEquals($expectedFields, $this->manager->getVariantFields($product));
    }

    public function testCreateMatrixCollection()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);
        $productUnit = new ProductUnit();
        $product->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($productUnit));

        $variantFields = [
            [
                'name' => 'size',
                'values' => [
                    ['value' => 's', 'label' => 'Small'],
                    ['value' => 'm', 'label' => 'Medium'],
                ],
            ],
            [
                'name' => 'color',
                'values' => [
                    ['value' => 'red', 'label' => 'Red'],
                    ['value' => 'green', 'label' => 'Green'],
                ],
            ],
        ];

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red');
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');

        $this->variantAvailability->expects($this->at(0))
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProductSmallRed, $simpleProductMediumGreen]);

        $this->variantAvailability->expects($this->at(1))
            ->method('getVariantFieldValue')
            ->with($simpleProductSmallRed, 'size')
            ->willReturn('s');

        $this->variantAvailability->expects($this->at(2))
            ->method('getVariantFieldValue')
            ->with($simpleProductSmallRed, 'color')
            ->willReturn('red');

        $this->variantAvailability->expects($this->at(3))
            ->method('getVariantFieldValue')
            ->with($simpleProductMediumGreen, 'size')
            ->willReturn('m');

        $this->variantAvailability->expects($this->at(4))
            ->method('getVariantFieldValue')
            ->with($simpleProductMediumGreen, 'color')
            ->willReturn('green');

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnMediumGreen->product = $simpleProductMediumGreen;

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowSmall, $rowMedium];

        $this->assertEquals($expectedCollection, $this->manager->createMatrixCollection($product, $variantFields));
    }

    public function testCalculateTotalQuantities()
    {
        $collection = $this->createCollection();

        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[0]->columns[1]->quantity = 3;

        $expectedTotals = [
            'total' => 8,
            'columns' => [5, 3],
        ];

        $this->assertEquals($expectedTotals, $this->manager->calculateTotalQuantities($collection));
    }

    public function testCalculateTotalPrice()
    {
        $simpleProduct00 = $this->getEntity(Product::class);
        $simpleProduct10 = $this->getEntity(Product::class);

        $productUnit = $this->getEntity(ProductUnit::class);

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[0]->columns[0]->product = $simpleProduct00;

        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

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

        $shoppingList = $this->getEntity(ShoppingList::class, ['lineItems' => [$lineItem00, $lineItem10]]);

        $subtotal = new Subtotal();
        $subtotal->setAmount(5);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($shoppingList)
            ->willReturn($subtotal);

        $expectedPrice = $this->getEntity(Price::class, ['value' => 5]);

        $this->assertEquals($expectedPrice, $this->manager->calculateTotalPrice($collection));
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
}
