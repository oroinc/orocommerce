<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Context\CheckoutContextDataConverter;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutContextDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutToOrderConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutToOrderConverter;

    /**
     * @var ContextDataConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderContextDataConverter;

    /**
     * @var CheckoutContextDataConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->checkoutToOrderConverter  = $this->createMock(CheckoutToOrderConverter::class);
        $this->orderContextDataConverter = $this->createMock(ContextDataConverterInterface::class);
        $this->converter                 = new CheckoutContextDataConverter(
            $this->checkoutToOrderConverter,
            $this->orderContextDataConverter
        );
    }

    public function testSupportsForWrongEntity()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->converter->supports($entity));
    }

    public function testSupportsForCheckoutWithNonShoppingListAsSource()
    {
        $this->assertFalse($this->converter->supports($this->getCheckout(\stdClass::class)));
    }

    public function testSupports()
    {
        $this->assertTrue($this->converter->supports($this->getCheckout()));
    }

    public function testGetContextDataWhenThrowsException()
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->getContextData($entity);
    }

    public function testGetContextData()
    {
        $checkout = $this->getCheckout();
        $order = new Order();
        $context = ['context' => 'data'];

        $this->checkoutToOrderConverter
            ->expects($this->any())
            ->method('getOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->orderContextDataConverter
            ->expects($this->any())
            ->method('getContextData')
            ->with($order)
            ->willReturn($context);

        $this->assertEquals($context, $this->converter->getContextData($checkout));
    }

    /**
     * @param string $sourceEntityClass
     * @return Checkout
     */
    private function getCheckout($sourceEntityClass = ShoppingList::class)
    {
        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn(new $sourceEntityClass);
        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        return $checkout;
    }
}
