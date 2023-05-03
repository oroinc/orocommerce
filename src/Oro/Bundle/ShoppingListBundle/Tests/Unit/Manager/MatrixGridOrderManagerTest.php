<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
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
use Oro\Component\Testing\ReflectionUtil;

class MatrixGridOrderManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $variantAvailability;

    /** @var EmptyMatrixGridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $emptyMatrixGridManager;

    /** @var MatrixGridOrderManager */
    private $manager;

    /** @var ProductUnitRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitRepository;

    protected function setUp(): void
    {
        $this->variantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->emptyMatrixGridManager = $this->createMock(EmptyMatrixGridInterface::class);

        $this->productUnitRepository = $this->createMock(ProductUnitRepository::class);

        /**
         * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine
         */
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine
            ->expects($this->any())
            ->method('getRepository')
            ->with(ProductUnit::class)
            ->willReturn($this->productUnitRepository);

        $this->manager = new MatrixGridOrderManager(
            PropertyAccess::createPropertyAccessor(),
            $this->variantAvailability,
            $this->emptyMatrixGridManager,
            $doctrine
        );
    }

    private function getProduct(int $id = null): Product
    {
        $product = new Product();
        if (null !== $id) {
            ReflectionUtil::setId($product, $id);
        }

        return $product;
    }

    private function getProductUnit(string $code = null): ProductUnit
    {
        $productUnit = new ProductUnit();
        if (null !== $code) {
            $productUnit->setCode($code);
        }

        return $productUnit;
    }

    private function getShoppingList(array $lineItems = []): ShoppingList
    {
        $shoppingList = new ShoppingList();
        foreach ($lineItems as $lineItem) {
            $shoppingList->addLineItem($lineItem);
        }

        return $shoppingList;
    }

    private function getLineItem(
        int $id = null,
        Product $product = null,
        float $quantity = null,
        ProductUnit $unit = null,
        Product $parentProduct = null
    ): LineItem {
        $lineItem = new LineItem();
        if (null !== $id) {
            ReflectionUtil::setId($lineItem, $id);
        }
        if (null !== $product) {
            $lineItem->setProduct($product);
        }
        if (null !== $quantity) {
            $lineItem->setQuantity($quantity);
        }
        if (null !== $unit) {
            $lineItem->setUnit($unit);
        }
        if (null !== $parentProduct) {
            $lineItem->setParentProduct($parentProduct);
        }

        return $lineItem;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetMatrixCollection()
    {
        $product = $this->getProduct(1);
        $productUnit = $this->getProductUnit();
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

        $productVariant = [
            $simpleProductSmallRed,
            $simpleProductMediumGreen,
            $simpleProductMediumRed,
            $simpleProductMediumNoColor
        ];
        $this->productUnitRepository
            ->expects($this->once())
            ->method('getProductIdsSupportUnit')
            ->with($productVariant, $productUnit)
            ->willReturn([1,2,4]);

        $simpleProductSmallRed->addUnitPrecision($productUnitPrecision);
        $simpleProductMediumGreen->addUnitPrecision($productUnitPrecision);
        $simpleProductMediumNoColor->addUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($productVariant);

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
        $rowSmall->columns = [$columnSmallRed];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [1 => $columnMediumGreen];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->dimensions = 2;
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowSmall, $rowMedium];
        $expectedCollection->columns = [
            ['value' => 'red', 'label' => 'Red'],
            ['value' => 'green', 'label' => 'Green'],
        ];

        $shoppingList = $this->getShoppingList([
            $this->getLineItem(null, $simpleProductSmallRed, 1, null, $product),
            $this->getLineItem(null, $simpleProductMediumGreen, 2, null, $product)
        ]);

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product, $shoppingList));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetMatrixCollectionForUnit()
    {
        $product = $this->getProduct(1);
        $productUnit = $this->getProductUnit('item');
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit);

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
                ['size', ['s' => 'Small' , 'm' => 'Medium']],
                ['color', ['red' => 'Red', 'green' => 'Green']]
            ]);

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red')->setId(1);
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green')->setId(2);
        $simpleProductMediumRed = (new ProductWithSizeAndColor())->setSize('m')->setColor('green')->setId(3);

        $productVariant = [
            $simpleProductSmallRed,
            $simpleProductMediumGreen,
            $simpleProductMediumRed
        ];
        $this->productUnitRepository
            ->expects($this->once())
            ->method('getProductIdsSupportUnit')
            ->with($productVariant, $productUnit)
            ->willReturn([1,2]);

        $simpleProductSmallRed->addUnitPrecision($productUnitPrecision);
        $simpleProductMediumGreen->addUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($productVariant);

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

        $productUnit1 = $this->getProductUnit('item');
        $productUnit2 = $this->getProductUnit('item');
        $productUnit3 = $this->getProductUnit('each');

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowSmall, $rowMedium];

        $shoppingList = $this->getShoppingList([
            $this->getLineItem(null, $simpleProductSmallRed, 1, $productUnit1, $product),
            $this->getLineItem(null, $simpleProductMediumGreen, 2, $productUnit2, $product),
            $this->getLineItem(null, $simpleProductMediumGreen, 4, $productUnit3, $product)
        ]);

        $this->assertEquals(
            $expectedCollection,
            $this->manager->getMatrixCollectionForUnit($product, $productUnit, $shoppingList)
        );
    }

    public function testGetMatrixCollectionNoVariantFields()
    {
        $product = $this->getProduct(1);
        $productUnit = $this->getProductUnit();
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

        $shoppingList = $this->getShoppingList();

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product, $shoppingList));
    }

    public function testGetMatrixCollectionForUnitNoVariantFields(): void
    {
        $product = $this->getProduct(1);
        $productUnit = $this->getProductUnit();
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

        $shoppingList = $this->getShoppingList();

        $this->assertEquals(
            $expectedCollection,
            $this->manager->getMatrixCollectionForUnit($product, $productUnit, $shoppingList)
        );
    }

    public function testGetMatrixCollectionWithBoolean()
    {
        $product = $this->getProduct();
        $productUnit = $this->getProductUnit();
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([
                'discount' => [true => true, false => true],
                'inSale' => [true => true, false => true],
            ]);
        $this->variantAvailability->expects($this->exactly(2))
            ->method('getVariantFieldValues')
            ->willReturnMap([
                ['discount', [1 => 'Yes', 0 => 'No']],
                ['inSale', [1 => 'Yes', 0 => 'No']]
            ]);

        $simpleProductNoDiscountNotInSale = (new ProductWithInSaleAndDiscount())->setDiscount(false)
            ->setInSale(false)
            ->setId(1);
        $simpleProductNoDiscountInSale = (new ProductWithInSaleAndDiscount())->setDiscount(false)
            ->setInSale(true)
            ->setId(2);
        $simpleProductDiscountNotInSale = (new ProductWithInSaleAndDiscount())->setDiscount(true)
            ->setInSale(false)
            ->setId(3);

        $productVariant = [
            $simpleProductNoDiscountNotInSale,
            $simpleProductNoDiscountInSale,
            $simpleProductDiscountNotInSale
        ];
        $this->productUnitRepository->expects($this->once())
            ->method('getProductIdsSupportUnit')
            ->with($productVariant, $productUnit)
            ->willReturn([1,2]);

        $simpleProductNoDiscountNotInSale->addUnitPrecision($productUnitPrecision);
        $simpleProductNoDiscountInSale->addUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($productVariant);
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
        $rowYes->columns = [$columnDiscountInSale];

        $rowNo = new MatrixCollectionRow();
        $rowNo->label = 'No';
        $rowNo->columns = [1 => $columnNotDiscountNotInSale];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->dimensions = 2;
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowYes, $rowNo];
        $expectedCollection->columns = [
            ['value' => 1, 'label' => 'Yes'],
            ['value' => 0, 'label' => 'No'],
        ];

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product));
    }

    /**
     * @dataProvider getProviderForConvertMatrixIntoLineItems
     */
    public function testConvertMatrixIntoLineItems(array $requiredCollection, array $expectedLineItems)
    {
        $productUnit = $this->getProductUnit();

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

        $product = $this->getProduct();
        $product->setType(Product::TYPE_CONFIGURABLE);

        $this->assertEquals(
            $expectedLineItems,
            $this->manager->convertMatrixIntoLineItems($collection, $product, $requiredCollection)
        );
    }

    public function getProviderForConvertMatrixIntoLineItems(): array
    {
        $productUnit = $this->getProductUnit();

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red');
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');

        $product = $this->getProduct();
        $product->setType(Product::TYPE_CONFIGURABLE);

        $lineItem1 = $this->getLineItem(null, $simpleProductSmallRed, 1, $productUnit, $product);
        $lineItem2 = $this->getLineItem(null, $simpleProductMediumGreen, 4, $productUnit, $product);

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
        $product = $this->getProduct(1);
        $productUnitEach = $this->getProductUnit('each');
        $productUnitItem = $this->getProductUnit('item');

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

        $productVariant = [$simpleProductSmallRed, $simpleProductMediumGreen, $simpleProductMediumRed];
        $this->productUnitRepository
            ->expects($this->once())
            ->method('getProductIdsSupportUnit')
            ->with($productVariant, $productUnitEach)
            ->willReturn([2,4]);

        $simpleProductSmallRed->addUnitPrecision($productUnitPrecisionEach);
        $simpleProductMediumRed->addUnitPrecision($productUnitPrecisionEach);
        $simpleProductMediumGreen->addUnitPrecision($productUnitPrecisionItem);

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($productVariant);
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
        $rowSmall->columns = [$columnSmallRed];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->dimensions = 2;
        $expectedCollection->unit = $productUnitEach;
        $expectedCollection->rows = [$rowSmall, $rowMedium];
        $expectedCollection->columns = [
            ['value' => 'red', 'label' => 'Red'],
            ['value' => 'green', 'label' => 'Green'],
        ];

        $shoppingList = $this->getShoppingList([
            $this->getLineItem(null, $simpleProductSmallRed, 1, $productUnitEach, $product),
            $this->getLineItem(null, $simpleProductMediumRed, 2, $productUnitItem, $product),
            $this->getLineItem(null, $simpleProductMediumRed, 3, $productUnitEach, $product)
        ]);

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product, $shoppingList));
    }

    public function testAddEmptyMatrixIfAllowed()
    {
        $shoppingList = new ShoppingList();
        $product = $this->getProduct(1);
        $lineItems = [$this->getLineItem(1), $this->getLineItem(2)];

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
        $product = $this->getProduct(1);
        $lineItems = [$this->getLineItem(1), $this->getLineItem(2)];

        $this->emptyMatrixGridManager->expects($this->once())
            ->method('isAddEmptyMatrixAllowed')
            ->with($lineItems)
            ->willReturn(false);

        $this->emptyMatrixGridManager->expects($this->never())
            ->method('addEmptyMatrix');

        $this->manager->addEmptyMatrixIfAllowed($shoppingList, $product, $lineItems);
    }
}
