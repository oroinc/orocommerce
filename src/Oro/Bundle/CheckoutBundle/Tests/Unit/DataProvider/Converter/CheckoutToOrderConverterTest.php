<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class CheckoutToOrderConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutLineItemsManager;

    /**
     * @var MapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mapper;

    /**
     * @var CheckoutToOrderConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->mapper = $this->createMock(MapperInterface::class);
        $this->converter = new CheckoutToOrderConverter(
            $this->checkoutLineItemsManager,
            $this->mapper
        );
    }

    public function testGetOrder()
    {
        $checkout = new Checkout();
        $order = new Order();

        $lineItems = new ArrayCollection([new OrderLineItem()]);
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->mapper->expects($this->once())
            ->method('map')
            ->with($checkout, ['lineItems' => $lineItems])
            ->willReturn($order);

        $this->assertSame($order, $this->converter->getOrder($checkout));

        return $order;
    }

    /**
     * Test that result of the 2nd call of the service with arguments
     * that has the same hash will be returned from the cache
     */
    public function testGetOrderCached()
    {
        $checkout = new Checkout();

        $order = $this->testGetOrder();

        $this->assertSame($order, $this->converter->getOrder($checkout));
    }
}
