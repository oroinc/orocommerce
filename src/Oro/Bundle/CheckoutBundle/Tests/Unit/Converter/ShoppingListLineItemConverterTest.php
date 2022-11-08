<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Converter\ShoppingListLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListLineItemConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new ShoppingListLineItemConverter();
    }

    /**
     * @dataProvider isSourceSupportedDataProvider
     */
    public function testIsSourceSupported(bool $expected, mixed $source)
    {
        $this->assertEquals($expected, $this->converter->isSourceSupported($source));
    }

    public function isSourceSupportedDataProvider(): array
    {
        return [
            'positive' => ['expected' => true, 'source' => $this->createMock(ShoppingList::class)],
            'unsupported instance' => ['expected' => false, 'source' => new \stdClass],
        ];
    }

    public function testConvert()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $lineItem = $this->createMock(LineItem::class);

        $shoppingList->expects($this->once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$lineItem]));

        $product = $this->createMock(Product::class);
        $parentProduct = $this->createMock(Product::class);
        $productUnit = $this->createMock(ProductUnit::class);

        $lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects($this->once())
            ->method('getParentProduct')
            ->willReturn($parentProduct);
        $lineItem->expects($this->once())
            ->method('getProductSku')
            ->willReturn('SKU');
        $lineItem->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $lineItem->expects($this->once())
            ->method('getProductUnitCode')
            ->willReturn('UNIT_CODE');
        $lineItem->expects($this->once())
            ->method('getNotes')
            ->willReturn('Notes');
        $lineItem->expects($this->once())
            ->method('getQuantity')
            ->willReturn(1);

        /** @var CheckoutLineItem[] $items */
        $items = $this->converter->convert($shoppingList);
        $this->assertInstanceOf(ArrayCollection::class, $items);
        $this->assertCount(1, $items);

        foreach ($items as $item) {
            $this->assertInstanceOf(CheckoutLineItem::class, $item);
            $this->assertSame($product, $item->getProduct());
            $this->assertSame($parentProduct, $item->getParentProduct());
            $this->assertSame('SKU', $item->getProductSku());
            $this->assertSame($productUnit, $item->getProductUnit());
            $this->assertSame('UNIT_CODE', $item->getProductUnitCode());
            $this->assertSame('Notes', $item->getComment());
            $this->assertSame(1, $item->getQuantity());
            $this->assertFalse($item->isPriceFixed());
        }
    }
}
