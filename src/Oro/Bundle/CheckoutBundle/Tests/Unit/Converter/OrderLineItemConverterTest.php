<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Converter\OrderLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\InventoryBundle\Provider\InventoryQuantityProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderLineItemConverterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var InventoryQuantityProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $quantityProvider;

    /** @var OrderLineItemConverter */
    protected $converter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->quantityProvider = $this->createMock(InventoryQuantityProviderInterface::class);

        $this->converter = new OrderLineItemConverter($this->quantityProvider);
    }

    public function testIsSourceSupported()
    {
        $this->assertTrue($this->converter->isSourceSupported(new Order()));
        $this->assertFalse($this->converter->isSourceSupported(new \stdClass()));
    }

    public function testConvertWithCanDecrement()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $parentProduct */
        $parentProduct = $this->getEntity(Product::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'unit-code']);
        $price = Price::create(100, 'USD');

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProduct($product)
            ->setFromExternalSource(false)
            ->setParentProduct($parentProduct)
            ->setProductUnit($productUnit)
            ->setFreeFormProduct('free form product')
            ->setProductSku('PSKU')
            ->setProductUnit($productUnit)
            ->setProductUnitCode('unit-code')
            ->setQuantity(10)
            ->setPrice($price)
            ->setPriceType(0)
            ->setComment('comment');

        $order = new Order();
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $checkoutLineItem = new CheckoutLineItem();
        $checkoutLineItem
            ->setFromExternalSource(false)
            ->setPriceFixed(false)
            ->setProduct($product)
            ->setParentProduct($parentProduct)
            ->setFreeFormProduct('free form product')
            ->setProductSku('PSKU')
            ->setProductUnit($productUnit)
            ->setProductUnitCode('unit-code')
            ->setQuantity(10)
            ->setPrice($price)
            ->setPriceType(0)
            ->setComment('comment');

        $this->quantityProvider->expects($this->once())
            ->method('canDecrement')
            ->with($product)
            ->willReturn(true);

        $this->quantityProvider->expects($this->once())
            ->method('getAvailableQuantity')
            ->with($product, $productUnit)
            ->willReturn(15);

        /* @var $items Collection|CheckoutLineItem[] */
        $items = $this->converter->convert($order);

        $this->assertEquals([$checkoutLineItem], $items->toArray());
    }

    public function testConvert()
    {
        $product = $this->getEntity(Product::class, ['id' => 2]);

        $oderLineItem = new OrderLineItem();
        $oderLineItem->setProduct($product);

        $order = new Order();
        $order->setLineItems(new ArrayCollection([$oderLineItem]));

        $this->quantityProvider->expects($this->once())
            ->method('canDecrement')
            ->with($product)
            ->willReturn(false);

        $this->quantityProvider->expects($this->never())
            ->method('getAvailableQuantity');

        /* @var $items Collection|CheckoutLineItem[] */
        $items = $this->converter->convert($order);

        $this->assertEquals([], $items->toArray());
    }

    public function testConvertWithoutAvailableQuantity()
    {
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'unit-code']);

        $oderLineItem = new OrderLineItem();
        $oderLineItem->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity(10);

        $order = new Order();
        $order->setLineItems(new ArrayCollection([$oderLineItem]));

        $this->quantityProvider->expects($this->once())
            ->method('canDecrement')
            ->with($product)
            ->willReturn(true);

        $this->quantityProvider->expects($this->once())
            ->method('getAvailableQuantity')
            ->with($product, $productUnit)
            ->willReturn(0);

        /* @var $items Collection|CheckoutLineItem[] */
        $items = $this->converter->convert($order);

        $this->assertEquals([], $items->toArray());
    }

    public function testConvertWithLessAvailableQuantity()
    {
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'unit-code']);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProduct($product)
            ->setProductUnit($productUnit)
            ->setFromExternalSource(true)
            ->setQuantity(10);

        $order = new Order();
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $checkoutLineItem = new CheckoutLineItem();
        $checkoutLineItem
            ->setFromExternalSource(true)
            ->setPriceFixed(false)
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity(5);

        $this->quantityProvider->expects($this->once())
            ->method('canDecrement')
            ->with($product)
            ->willReturn(true);

        $this->quantityProvider->expects($this->once())
            ->method('getAvailableQuantity')
            ->with($product, $productUnit)
            ->willReturn(5);

        /* @var $items Collection|CheckoutLineItem[] */
        $items = $this->converter->convert($order);

        $this->assertEquals([$checkoutLineItem], $items->toArray());
    }

    public function testConvertWithUnlimitedQuantity()
    {
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'unit-code']);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProduct($product)
            ->setProductUnit($productUnit)
            ->setFromExternalSource(true)
            ->setQuantity(10);

        $order = new Order();
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $checkoutLineItem = new CheckoutLineItem();
        $checkoutLineItem
            ->setFromExternalSource(true)
            ->setPriceFixed(false)
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity(10);

        $this->quantityProvider->expects($this->once())
            ->method('canDecrement')
            ->with($product)
            ->willReturn(false);

        $this->quantityProvider->expects($this->never())
            ->method('getAvailableQuantity');

        /* @var $items Collection|CheckoutLineItem[] */
        $items = $this->converter->convert($order);

        $this->assertEquals([$checkoutLineItem], $items->toArray());
    }
}
