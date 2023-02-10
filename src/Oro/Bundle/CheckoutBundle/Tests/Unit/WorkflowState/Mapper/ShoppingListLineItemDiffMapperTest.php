<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapper;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListLineItemDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shipContextProvider;

    protected function setUp(): void
    {
        $this->shipContextProvider = $this->createMock(CheckoutShippingContextProvider::class);

        parent::setUp();
    }

    public function testGetName(): void
    {
        $this->assertEquals('shopping_list_line_item', $this->mapper->getName());
    }

    public function testGetCurrentState(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());
        $shoppingList->addLineItem(new LineItem());

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($shoppingList);

        $prod1 = $this->getProduct('SKU123', 'in_stock');
        $prod2 = $this->getProduct('SKU123', 'in_stock');
        $price1 = $this->getPrice(120);
        $price2 = $this->getPrice(10);
        $weight1 = $this->getWeight(10);
        $weight2 = $this->getWeight(1);
        $dms1 = $this->getDimension(1, 2, 3);
        $dms2 = $this->getDimension(1, 2, 3);

        $item1 = $this->getShippingLineItem('set', 1, 'SKU123', $price1, $weight1, $dms1, $prod1);
        $item2 = $this->getShippingLineItem('item', 1, 'SKU123', $price2, $weight2, $dms2, $prod2);

        $shipContext = $this->createMock(ShippingContextInterface::class);
        $shipContext->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$item1, $item2]);
        $this->shipContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($shipContext);

        $result = $this->mapper->getCurrentState($checkout);

        $this->assertEquals(
            [
                'sSKU123-uset-q1-pUSD120-w10kg-d1x2x3cm-iin_stock',
                'sSKU123-uitem-q1-pUSD10-w1kg-d1x2x3cm-iin_stock'
            ],
            $result
        );
    }

    public function testGetCurrentStateNoProduct(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());
        $shoppingList->addLineItem(new LineItem());

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($shoppingList);

        $price1 = $this->getPrice(120);
        $price2 = $this->getPrice(10);
        $weight1 = $this->getWeight(10);
        $weight2 = $this->getWeight(1);
        $dms1 = $this->getDimension(1, 2, 3);
        $dms2 = $this->getDimension(1, 2, 3);

        $item1 = $this->getShippingLineItem('set', 1, 'SKU123', $price1, $weight1, $dms1);
        $item2 = $this->getShippingLineItem('item', 1, 'SKU123', $price2, $weight2, $dms2);

        $shipContext = $this->createMock(ShippingContextInterface::class);
        $shipContext->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$item1, $item2]);
        $this->shipContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($shipContext);

        $result = $this->mapper->getCurrentState($checkout);

        $this->assertEquals(
            [
                's-uset-q1-pUSD120-w10kg-d1x2x3cm-i',
                's-uitem-q1-pUSD10-w1kg-d1x2x3cm-i'
            ],
            $result
        );
    }

    public function testIsStatesEqualTrue(): void
    {
        $state1 = [
            'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
            'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
        ];

        $state2 = [
            'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
            'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
        ];

        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, $state1, $state2));
    }

    /**
     * @dataProvider isStatesEqualFalseProvider
     */
    public function testIsStatesEqualFalse(array $state1, array $state2): void
    {
        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, $state1, $state2));
    }

    public function isStatesEqualFalseProvider(): array
    {
        return [
            'with more items for state2' => [
                'state1' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
                'state2' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock',
                    'sSKU456-uitem-q1-pUSD100-w10kg-d1x1x1cm-iin_stock'
                ],
            ],
            'with more items for state1' => [
                'state1' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock',
                    'sSKU456-uitem-q1-pUSD100-w10kg-d1x1x1cm-iin_stock'
                ],
                'state2' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
            ],
            'with different sku' => [
                'state1' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
                'state2' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU456-uitem-q1-pUSD100-w10kg-d1x1x1cm-iin_stock'
                ],
            ],
            'with different quantity' => [
                'state1' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
                'state2' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q5-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
            ],
            'with different prices' => [
                'state1' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
                'state2' => [
                    'sSKU123-uset-q1-pUSD240-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
            ],
            'with different inventory status' => [
                'state1' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
                'state2' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iout_of_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
            ],
            'with different weight' => [
                'state1' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
                ],
                'state2' => [
                    'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                    'sSKU123-uitem-q1-pUSD10-w2kg-d1x1x1cm-iin_stock'
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getMapper(): ShoppingListLineItemDiffMapper|CheckoutStateDiffMapperInterface
    {
        return new ShoppingListLineItemDiffMapper($this->shipContextProvider);
    }

    private function getShippingLineItem(
        string $unitCode,
        int $quantity,
        string $sku,
        Price $price,
        Weight $weight,
        Dimensions $dimension,
        ?StubProduct $product = null
    ): ShippingLineItem {
        return new ShippingLineItem(
            [
                ShippingLineItem::FIELD_PRICE => $price,
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
                ShippingLineItem::FIELD_QUANTITY => $quantity,
                ShippingLineItem::FIELD_PRODUCT_SKU => $sku,
                ShippingLineItem::FIELD_WEIGHT => $weight,
                ShippingLineItem::FIELD_DIMENSIONS => $dimension,
                ShippingLineItem::FIELD_PRODUCT => $product,
            ]
        );
    }

    private function getProduct(string $sku, string $inventoryStatusCode): StubProduct
    {
        $inventoryStatus = new TestEnumValue($inventoryStatusCode, $inventoryStatusCode);
        $product = $this->createMock(StubProduct::class);
        $product->expects($this->any())
            ->method('getSkuUppercase')
            ->willReturn($sku);
        $product->expects($this->any())
            ->method('getInventoryStatus')
            ->willReturn($inventoryStatus);

        return $product;
    }

    private function getPrice(int $value): Price
    {
        $price1 = new Price();
        $price1->setValue($value)
            ->setCurrency('USD');

        return $price1;
    }

    private function getWeight(int $value): Weight
    {
        $weight = new Weight();
        $weight->setValue($value);
        $weightUnit = new WeightUnit();
        $weightUnit->setCode('kg');
        $weight->setUnit($weightUnit);

        return $weight;
    }

    private function getDimension(int $height, int $length, int $weight): Dimensions
    {
        $dimensionValue = new DimensionsValue();
        $dimensionValue->setHeight($height)
            ->setLength($length)
            ->setWidth($weight);
        $dimensionUnit = new LengthUnit();
        $dimensionUnit->setCode('cm');
        $dimension = new Dimensions();
        $dimension->setValue($dimensionValue)
            ->setUnit($dimensionUnit);

        return $dimension;
    }
}
