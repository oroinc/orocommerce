<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderDiscountContextConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class OrderDiscountContextConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderDiscountContextConverter
     */
    protected $orderDiscountContextConverter;

    /** @var  Order|\PHPUnit_Framework_MockObject_MockObject */
    protected $order;

    protected function setUp()
    {
        $this->order = $this->createMock(Order::class);
        $this->orderDiscountContextConverter = new OrderDiscountContextConverter();
    }

    public function testSupports()
    {
        self::assertTrue($this->orderDiscountContextConverter->supports($this->order));
    }

    public function testSupportsForWrongEntity()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->orderDiscountContextConverter->supports($entity));
    }

    public function testConvert()
    {
        $this->order->expects($this->once())->method('getLineItems')
            ->willReturn($this->prepareOrderLineItemCollection());
        $this->order->expects($this->once())->method('getSubtotal')->willReturn(0);

        $this->assertInstanceOf(
            DiscountContext::class,
            $this->orderDiscountContextConverter->convert($this->order)
        );
    }

    protected function prepareOrderLineItemCollection()
    {
        $orderLineItem = $this->createMock(OrderLineItem::class);
        $orderLineItem->expects($this->once())->method('getQuantity')->willReturn(1.0);
        $orderLineItem->expects($this->once())->method('getQuantity')->willReturn(1.0);
        $orderLineItem->expects($this->once())->method('getPriceType')->willReturn('string');
        $orderLineItem->expects($this->once())->method('getPrice')
            ->willReturn((new Price())->setCurrency('USD')->setValue(10.0));
        $orderLineItem->expects($this->once())->method('getProductUnit')
            ->willReturn($this->createMock(ProductUnit::class));
        $orderLineItem->expects($this->once())->method('getProduct')
            ->willReturn($this->createMock(Product::class));

        return new ArrayCollection([$orderLineItem]);
    }
}
