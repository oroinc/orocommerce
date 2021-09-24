<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\EmptyMatrixGridInterface;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub\ProductWithInSaleAndDiscount;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub\ProductWithSizeAndColor;
use Oro\Component\Testing\Unit\EntityTrait;

class MatrixGridOrderManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $variantAvailability;

    /** @var EmptyMatrixGridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $emptyMatrixGridManager;

    /** @var MatrixGridOrderManager */
    private $manager;

    protected function setUp(): void
    {
        $this->variantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->emptyMatrixGridManager = $this->createMock(EmptyMatrixGridInterface::class);

        $this->manager = new MatrixGridOrderManager(
            $this->getPropertyAccessor(),
            $this->variantAvailability,
            $this->emptyMatrixGridManager
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetMatrixCollection()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productUnit = new ProductUnit();
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([
                'size' => [
                    's' => true,
                    'm' => true,
                ],
                'color' => [
                    'red' => true,
                    'green' => true,
                ],
            ]);

        $this->variantAvailability->expects($this->exactly(2))
            ->method('getVariantFieldValues')
            ->willReturnMap([
                ['size', ['s' => 'Small', 'm' => 'Medium']],
                ['color', ['red' => 'Red', 'green' => 'Green']]
            ]);

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red')->setId(1);
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green')->setId(2);
        $simpleProductMediumRed = (new ProductWithSizeAndColor())->setSize('m')->setColor('green')->setId(3);
        $simpleProductMediumNoColor = (new ProductWithSizeAndColor())->setSize('m')->setId(4);

        $simpleProductSmallRed->addUnitPrecision($productUnitPrecision);
        $simpleProductMediumGreen->addUnitPrecision($productUnitPrecision);
        $simpleProductMediumNoColor->addUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([
                $simpleProductSmallRed,
                $simpleProductMediumGreen,
                $simpleProductMediumRed,
                $simpleProductMediumNoColor
            ]);

        $this->variantAvailability->expects($this->exactly(6))
            ->method('getVariantFieldScalarValue')
            ->willReturnMap([
                [$simpleProductSmallRed, 'size', 's'],
                [$simpleProductSmallRed, 'color', 'red'],
                [$simpleProductMediumGreen, 'size', 'm'],
                [$simpleProductMediumGreen, 'color', 'green'],
                [$simpleProductMediumNoColor, 'size', 'm'],
                [$simpleProductMediumNoColor, 'color', null]
            ]);

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->label = 'Red';
        $columnSmallGreen->label = 'Green';
        $columnMediumRed->label = 'Red';
        $columnMediumGreen->label = 'Green';

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnSmallRed->quantity = 1;
        $columnMediumGreen->product = $simpleProductMediumGreen;
        $columnMediumGreen->quantity = 2;

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->label = 'Small';
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowSmall, $rowMedium];

        $lineItems = new ArrayCollection();
        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductSmallRed,
            'quantity' => 1,
            'parentProduct' => $product
        ]);
        $lineItems->add($lineItem);
        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductMediumGreen,
            'quantity' => 2,
            'parentProduct' => $product
        ]);
        $lineItems->add($lineItem);

        $shoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => $lineItems
        ]);

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product, $shoppingList));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetMatrixCollectionForUnit()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);

        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'item']);

        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit);

        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([
                'size' => [
                    's' => true,
                    'm' => true,
                ],
                'color' => [
                    'red' => true,
                    'green' => true,
                ],
            ]);

        $this->variantAvailability->expects($this->exactly(2))
            ->method('getVariantFieldValues')
            ->willReturnMap([
                ['size', ['s' => 'Small', 'm' => 'Medium']],
                ['color', ['red' => 'Red', 'green' => 'Green']]
            ]);

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red')->setId(1);
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green')->setId(2);
        $simpleProductMediumRed = (new ProductWithSizeAndColor())->setSize('m')->setColor('green')->setId(3);

        $simpleProductSmallRed->addUnitPrecision($productUnitPrecision);
        $simpleProductMediumGreen->addUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProductSmallRed, $simpleProductMediumGreen, $simpleProductMediumRed]);

        $this->variantAvailability->expects($this->exactly(4))
            ->method('getVariantFieldScalarValue')
            ->willReturnMap([
                [$simpleProductSmallRed, 'size', 's'],
                [$simpleProductSmallRed, 'color', 'red'],
                [$simpleProductMediumGreen, 'size', 'm'],
                [$simpleProductMediumGreen, 'color', 'green'],
            ]);

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->label = 'Red';
        $columnSmallGreen->label = 'Green';
        $columnMediumRed->label = 'Red';
        $columnMediumGreen->label = 'Green';

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnSmallRed->quantity = 1;
        $columnMediumGreen->product = $simpleProductMediumGreen;
        $columnMediumGreen->quantity = 2;

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->label = 'Small';
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $productUnit1 = $this->getEntity(ProductUnit::class, ['code' => 'item']);
        $productUnit2 = $this->getEntity(ProductUnit::class, ['code' => 'item']);
        $productUnit3 = $this->getEntity(ProductUnit::class, ['code' => 'each']);

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowSmall, $rowMedium];

        $lineItems = new ArrayCollection();
        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductSmallRed,
            'unit' => $productUnit1,
            'quantity' => 1,
            'parentProduct' => $product
        ]);
        $lineItems->add($lineItem);
        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductMediumGreen,
            'unit' => $productUnit2,
            'quantity' => 2,
            'parentProduct' => $product
        ]);
        $lineItems->add($lineItem);
        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductMediumGreen,
            'unit' => $productUnit3,
            'quantity' => 4,
            'parentProduct' => $product
        ]);
        $lineItems->add($lineItem);

        $shoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => $lineItems
        ]);

        $this->assertEquals(
            $expectedCollection,
            $this->manager->getMatrixCollectionForUnit($product, $productUnit, $shoppingList)
        );
    }

    public function testGetMatrixCollectionNoVariantFields()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productUnit = new ProductUnit();
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([]);

        $this->variantAvailability->expects($this->never())
            ->method('getVariantFieldValues');

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([]);

        $this->variantAvailability->expects($this->never())
            ->method('getVariantFieldScalarValue');

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;

        $shoppingList = $this->getEntity(ShoppingList::class);

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product, $shoppingList));
    }

    public function testGetMatrixCollectionForUnitNoVariantFields(): void
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productUnit = new ProductUnit();
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([]);

        $this->variantAvailability->expects($this->never())
            ->method('getVariantFieldValues');

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([]);

        $this->variantAvailability->expects($this->never())
            ->method('getVariantFieldScalarValue');

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;

        $shoppingList = $this->getEntity(ShoppingList::class);

        $this->assertEquals(
            $expectedCollection,
            $this->manager->getMatrixCollectionForUnit($product, $productUnit, $shoppingList)
        );
    }

    public function testGetMatrixCollectionWithBoolean()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);
        $productUnit = new ProductUnit();
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([
                'discount' => [
                    true => true,
                    false => true,
                ],
                'inSale' => [
                    true => true,
                    false => true,
                ],
            ]);

        $this->variantAvailability->expects($this->exactly(2))
            ->method('getVariantFieldValues')
            ->withConsecutive(['discount'], ['inSale'])
            ->willReturnOnConsecutiveCalls(
                [1 => 'Yes', 0 => 'No'],
                [1 => 'Yes', 0 => 'No']
            );

        $simpleProductNoDiscountNotInSale = (new ProductWithInSaleAndDiscount())->setDiscount(false)->setInSale(false);
        $simpleProductNoDiscountInSale = (new ProductWithInSaleAndDiscount())->setDiscount(false)->setInSale(true);
        $simpleProductDiscountNotInSale = (new ProductWithInSaleAndDiscount())->setDiscount(true)->setInSale(false);

        $simpleProductNoDiscountNotInSale->addUnitPrecision($productUnitPrecision);
        $simpleProductNoDiscountInSale->addUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([
                $simpleProductNoDiscountNotInSale,
                $simpleProductNoDiscountInSale,
                $simpleProductDiscountNotInSale
            ]);

        $this->variantAvailability->expects($this->exactly(4))
            ->method('getVariantFieldScalarValue')
            ->willReturnMap([
                [$simpleProductNoDiscountNotInSale, 'discount', true],
                [$simpleProductNoDiscountNotInSale, 'inSale', true],
                [$simpleProductNoDiscountInSale, 'discount', false],
                [$simpleProductNoDiscountInSale, 'inSale', false],
            ]);

        $columnDiscountInSale = new MatrixCollectionColumn();
        $columnDiscountNotInSale = new MatrixCollectionColumn();
        $columnNotDiscountInSale = new MatrixCollectionColumn();
        $columnNotDiscountNotInSale = new MatrixCollectionColumn();

        $columnDiscountInSale->label = 'Yes';
        $columnDiscountNotInSale->label = 'No';
        $columnNotDiscountInSale->label = 'Yes';
        $columnNotDiscountNotInSale->label = 'No';

        $columnDiscountInSale->product = $simpleProductNoDiscountNotInSale;
        $columnNotDiscountNotInSale->product = $simpleProductNoDiscountInSale;

        $columnDiscountInSale->quantity = null;
        $columnDiscountNotInSale->quantity = null;
        $columnNotDiscountInSale->quantity = null;
        $columnNotDiscountNotInSale->quantity = null;

        $rowYes = new MatrixCollectionRow();
        $rowYes->label = 'Yes';
        $rowYes->columns = [$columnDiscountInSale, $columnDiscountNotInSale];

        $rowNo = new MatrixCollectionRow();
        $rowNo->label = 'No';
        $rowNo->columns = [$columnNotDiscountInSale, $columnNotDiscountNotInSale];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowYes, $rowNo];

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product));
    }

    /**
     * @param array $requiredCollection
     * @param array $expectedLineItems
     *
     * @dataProvider getProviderForConvertMatrixIntoLineItems
     */
    public function testConvertMatrixIntoLineItems($requiredCollection, $expectedLineItems)
    {
        $productUnit = $this->getEntity(ProductUnit::class);

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red');
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnSmallRed->quantity = 1;
        $columnMediumGreen->product = $simpleProductMediumGreen;
        $columnMediumGreen->quantity = 4;

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->label = 'Small';
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $collection = new MatrixCollection();
        $collection->unit = $productUnit;
        $collection->rows = [$rowSmall, $rowMedium];

        /** @var Product $product */
        $product = $this->getEntity(Product::class);
        $product->setType(Product::TYPE_CONFIGURABLE);

        $this->assertEquals(
            $expectedLineItems,
            $this->manager->convertMatrixIntoLineItems($collection, $product, $requiredCollection)
        );
    }

    /**
     * @return array
     */
    public function getProviderForConvertMatrixIntoLineItems()
    {
        $productUnit = $this->getEntity(ProductUnit::class);

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red');
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);
        $product->setType(Product::TYPE_CONFIGURABLE);

        $lineItem1 = $this->getEntity(LineItem::class, [
            'product' => $simpleProductSmallRed,
            'unit' => $productUnit,
            'quantity' => 1,
            'parentProduct' => $product
        ]);
        $lineItem2 = $this->getEntity(LineItem::class, [
            'product' => $simpleProductMediumGreen,
            'unit' => $productUnit,
            'quantity' => 4,
            'parentProduct' => $product
        ]);

        return [
            'without matrix collection' => [
                'requiredCollection' => [],
                'expectedLineItems' => []
            ],
            'empty matrix collection' => [
                'requiredCollection' => [
                    'rows' => []
                ],
                'expectedLineItems' => []
            ],
            'partial matrix collection' => [
                'requiredCollection' => [
                    'rows' => [
                        1 => [
                            'columns' => ['quantity' => 4]
                        ]
                    ]
                ],
                'expectedLineItems' => [$lineItem2]
            ],
            'full matrix collection' => [
                'requiredCollection' => [
                    'rows' => [
                        0 => [
                            'columns' => ['quantity' => 1]
                        ],
                        1 => [
                            'columns' => ['quantity' => 4]
                        ]
                    ]
                ],
                'expectedLineItems' => [$lineItem1, $lineItem2]
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetMatrixCollectionCheckQtyForDifferentUnits()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productUnitItem = (new ProductUnit())->setCode('item');

        $productUnitPrecisionEach = (new ProductUnitPrecision())->setUnit($productUnitEach);
        $productUnitPrecisionItem = (new ProductUnitPrecision())->setUnit($productUnitItem);

        $product->setPrimaryUnitPrecision($productUnitPrecisionEach);

        $this->variantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([
                'size' => [
                    's' => true,
                    'm' => true,
                ],
                'color' => [
                    'red' => true,
                    'green' => true,
                ],
            ]);

        $this->variantAvailability->expects($this->exactly(2))
            ->method('getVariantFieldValues')
            ->willReturnMap([
                ['size', ['s' => 'Small', 'm' => 'Medium']],
                ['color', ['red' => 'Red', 'green' => 'Green']]
            ]);

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red')->setId(2);
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green')->setId(3);
        $simpleProductMediumRed = (new ProductWithSizeAndColor())->setSize('m')->setColor('red')->setId(4);

        $simpleProductSmallRed->addUnitPrecision($productUnitPrecisionEach);
        $simpleProductMediumRed->addUnitPrecision($productUnitPrecisionEach);
        $simpleProductMediumGreen->addUnitPrecision($productUnitPrecisionItem);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProductSmallRed, $simpleProductMediumGreen, $simpleProductMediumRed]);

        $this->variantAvailability->expects($this->exactly(4))
            ->method('getVariantFieldScalarValue')
            ->willReturnMap([
                [$simpleProductSmallRed, 'size', 's'],
                [$simpleProductSmallRed, 'color', 'red'],
                [$simpleProductMediumRed, 'size', 'm'],
                [$simpleProductMediumRed, 'color', 'red'],
            ]);

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->label = 'Red';
        $columnSmallGreen->label = 'Green';
        $columnMediumRed->label = 'Red';
        $columnMediumGreen->label = 'Green';

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnSmallRed->quantity = 1;
        $columnMediumRed->product = $simpleProductMediumRed;
        $columnMediumRed->quantity = 3;

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->label = 'Small';
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnitEach;
        $expectedCollection->rows = [$rowSmall, $rowMedium];

        $lineItems = new ArrayCollection();
        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductSmallRed,
            'quantity' => 1,
            'parentProduct' => $product,
            'unit' => $productUnitEach
        ]);
        $lineItems->add($lineItem);

        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductMediumRed,
            'quantity' => 2,
            'parentProduct' => $product,
            'unit' => $productUnitItem
        ]);
        $lineItems->add($lineItem);

        $lineItem = $this->getEntity(LineItem::class, [
            'product' => $simpleProductMediumRed,
            'quantity' => 3,
            'parentProduct' => $product,
            'unit' => $productUnitEach
        ]);
        $lineItems->add($lineItem);

        $shoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => $lineItems
        ]);

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product, $shoppingList));
    }

    public function testAddEmptyMatrixIfAllowed()
    {
        $shoppingList = new ShoppingList();
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $lineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
            $this->getEntity(LineItem::class, ['id' => 2]),
        ];

        $this->emptyMatrixGridManager->expects($this->once())
            ->method('isAddEmptyMatrixAllowed')
            ->with($lineItems)
            ->willReturn(true);

        $this->emptyMatrixGridManager->expects($this->once())
            ->method('addEmptyMatrix')
            ->with($shoppingList, $product);

        $this->manager->addEmptyMatrixIfAllowed($shoppingList, $product, $lineItems);
    }

    public function testAddEmptyMatrixIfNotAllowed()
    {
        $shoppingList = new ShoppingList();
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $lineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
            $this->getEntity(LineItem::class, ['id' => 2]),
        ];

        $this->emptyMatrixGridManager->expects($this->once())
            ->method('isAddEmptyMatrixAllowed')
            ->with($lineItems)
            ->willReturn(false);

        $this->emptyMatrixGridManager->expects($this->never())
            ->method('addEmptyMatrix');

        $this->manager->addEmptyMatrixIfAllowed($shoppingList, $product, $lineItems);
    }
}
