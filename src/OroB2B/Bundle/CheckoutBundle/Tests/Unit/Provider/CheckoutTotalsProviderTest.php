<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class CheckoutTotalsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutLineItemsManager;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalsProvider;

    /**
     * @var CheckoutTotalsProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->checkoutLineItemsManager = $this->getMockBuilder(CheckoutLineItemsManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->totalsProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()->getMock();
        $this->provider = new CheckoutTotalsProvider($this->checkoutLineItemsManager, $this->totalsProvider);
    }

    public function testGetTotalsArray()
    {
        $lineItems = new ArrayCollection();
        $price = Price::create(10, 'USD');
        $checkout = $this->getEntity(Checkout::class, [
            'shippingCost' => $price,
        ]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->totalsProvider->expects($this->once())
            ->method('enableRecalculation');

        $this->totalsProvider->expects($this->once())
            ->method('getTotalWithSubtotalsAsArray')
            ->will($this->returnCallback(function (Order $order) use ($lineItems, $price) {
                $this->assertSame($lineItems, $order->getLineItems());
                $this->assertSame($price, $order->getShippingCost());
            }));

        $this->provider->getTotalsArray($checkout);
    }
}
