<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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

    /** @var MatrixGridOrderManager */
    private $manager;

    protected function setUp()
    {
        $this->variantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);

        $this->manager = new MatrixGridOrderManager(
            $this->getPropertyAccessor(),
            $this->variantAvailability
        );
    }

    public function testGetMatrixCollection()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);
        $productUnit = new ProductUnit();
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->at(0))
            ->method('getVariantFieldsWithAvailability')
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

        $this->variantAvailability->expects($this->at(1))
            ->method('getAllVariantsByVariantFieldName')
            ->with('size')
            ->willReturn(['s' => 'Small', 'm' => 'Medium']);

        $this->variantAvailability->expects($this->at(2))
            ->method('getAllVariantsByVariantFieldName')
            ->with('color')
            ->willReturn(['red' => 'Red', 'green' => 'Green']);

        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red');
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');
        $simpleProductMediumRed = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');

        $simpleProductSmallRed->addUnitPrecision($productUnitPrecision);
        $simpleProductMediumGreen->addUnitPrecision($productUnitPrecision);

        $this->variantAvailability->expects($this->at(3))
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProductSmallRed, $simpleProductMediumGreen, $simpleProductMediumRed]);

        $this->variantAvailability->expects($this->at(4))
            ->method('getVariantFieldValue')
            ->with($simpleProductSmallRed, 'size')
            ->willReturn('s');

        $this->variantAvailability->expects($this->at(5))
            ->method('getVariantFieldValue')
            ->with($simpleProductSmallRed, 'color')
            ->willReturn('red');

        $this->variantAvailability->expects($this->at(6))
            ->method('getVariantFieldValue')
            ->with($simpleProductMediumGreen, 'size')
            ->willReturn('m');

        $this->variantAvailability->expects($this->at(7))
            ->method('getVariantFieldValue')
            ->with($simpleProductMediumGreen, 'color')
            ->willReturn('green');

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->label = 'Red';
        $columnSmallGreen->label = 'Green';
        $columnMediumRed->label = 'Red';
        $columnMediumGreen->label = 'Green';

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnMediumGreen->product = $simpleProductMediumGreen;

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->label = 'Small';
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $expectedCollection = new MatrixCollection();
        $expectedCollection->unit = $productUnit;
        $expectedCollection->rows = [$rowSmall, $rowMedium];

        $this->assertEquals($expectedCollection, $this->manager->getMatrixCollection($product));
    }

    public function testConvertMatrixIntoLineItems()
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

        $lineItem1 = $this->getEntity(LineItem::class, [
            'product' => $simpleProductSmallRed,
            'unit' => $productUnit,
            'quantity' => 1,
        ]);
        $lineItem2 = $this->getEntity(LineItem::class, [
            'product' => $simpleProductMediumGreen,
            'unit' => $productUnit,
            'quantity' => 4,
        ]);

        $this->assertEquals(
            [$lineItem1, $lineItem2],
            $this->manager->convertMatrixIntoLineItems($collection)
        );
    }
}
