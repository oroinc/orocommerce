<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\SingleUnitModeProvider;

class SingleUnitModeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitFieldsSettingsInterface */
    private $productUnitFieldsSettings;

    /** @var \PHPUnit_Framework_MockObject_MockObject|UnitVisibilityInterface */
    private $unitVisibility;

    /** @var SingleUnitModeProvider $unitModeProvider */
    protected $unitModeProvider;

    public function setUp()
    {
        $this->productUnitFieldsSettings = $this->getMockBuilder(ProductUnitFieldsSettingsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitVisibility = $this->getMockBuilder(UnitVisibilityInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->unitModeProvider = new SingleUnitModeProvider($this->productUnitFieldsSettings, $this->unitVisibility);
    }

    public function testGetProductsUnitSelectionVisibilities()
    {
        $productVisibility = true;
        $this->productUnitFieldsSettings->expects(static::once())
            ->method('isProductUnitSelectionVisible')
            ->willReturn($productVisibility);

        $product = new Product();
        $lineItem = (new LineItem())->setProduct($product);
        $shoppingList = (new ShoppingList())->addLineItem($lineItem);

        $productStatuses = $this->unitModeProvider->getProductsUnitSelectionVisibilities($shoppingList);

        static::assertSame([$product->getId() => $productVisibility], $productStatuses);
    }

    public function testGetProductsUnitSelectionVisibilitiesOnNull()
    {
        static::assertSame([], $this->unitModeProvider->getProductsUnitSelectionVisibilities());
    }

    public function testGetLineItemsUnitVisibilities()
    {
        $unit = new ProductUnit('each');
        $unitVisibility = true;
        $this->unitVisibility->expects(static::once())
            ->method('isUnitCodeVisible')
            ->with($unit->getCode())
            ->willReturn($unitVisibility);

        $product = new Product();
        $lineItem = (new LineItem())->setUnit($unit);
        $shoppingList = (new ShoppingList())->addLineItem($lineItem);

        $productStatuses = $this->unitModeProvider->getLineItemsUnitVisibilities($shoppingList);

        static::assertSame([$product->getId() => $unitVisibility], $productStatuses);
    }

    public function testGetLineItemsUnitVisibilitiesOnNull()
    {
        static::assertSame([], $this->unitModeProvider->getLineItemsUnitVisibilities());
    }
}
