<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\EventListener\ResolvePaymentTermListener;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutProvider;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

class ResolvePaymentTermListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutProvider;

    /**
     * @var ResolvePaymentTermEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var ResolvePaymentTermListener
     */
    protected $resolvePaymentTermListener;

    protected function setUp()
    {
        $this->event = new ResolvePaymentTermEvent();
        $this->checkoutProvider = $this
            ->getMockBuilder(CheckoutProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolvePaymentTermListener = new ResolvePaymentTermListener($this->checkoutProvider);
    }

    public function testOnResolvePaymentTermNoCheckout()
    {
        $this->checkoutProvider->expects($this->once())->method('getCurrent')->willReturn(null);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermNoQuoteDemand()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $checkout = $this->getMock(Checkout::class);

        $this->checkoutProvider->expects($this->once())->method('getCurrent')->willReturn($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn(null);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermBadCheckoutSource()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $checkout = $this->getMock(Checkout::class);
        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource **/
        $checkoutSource = $this->getMock(CheckoutSource::class);

        $this->checkoutProvider->expects($this->once())->method('getCurrent')->willReturn($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn($checkoutSource);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermNoPaymentTerm()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $checkout = $this->getMock(Checkout::class);
        /** @var QuoteDemand|\PHPUnit_Framework_MockObject_MockObject $checkoutSource **/
        $checkoutSource = $this->getMock(QuoteDemand::class);

        $this->checkoutProvider->expects($this->once())->method('getCurrent')->willReturn($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn($checkoutSource);
        $checkoutSource->expects($this->once())->method('getQuote')->willReturn(new Quote());

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTerm()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $checkout = $this->getMock(Checkout::class);
        /** @var QuoteDemand|\PHPUnit_Framework_MockObject_MockObject $checkoutSource **/
        $checkoutSource = $this->getMock(QuoteDemand::class);
        $paymentTerm = new PaymentTerm();
        $quote = new Quote();
        $quote->setPaymentTerm($paymentTerm);

        $this->checkoutProvider->expects($this->once())->method('getCurrent')->willReturn($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn($checkoutSource);
        $checkoutSource->expects($this->exactly(2))->method('getQuote')->willReturn($quote);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertSame($paymentTerm, $this->event->getPaymentTerm());
    }
}
