<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEvents;
use Oro\Bundle\CheckoutBundle\EventListener\ResolvePaymentTermListener;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

class ResolvePaymentTermListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

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
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);

        $this->resolvePaymentTermListener = new ResolvePaymentTermListener($this->requestStack, $this->eventDispatcher);
    }

    public function testOnResolvePaymentTermNoRequest()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testGetCurrentNoRouteOnRequest()
    {
        $request = new Request();
        $request->attributes->set('_route', '');

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermNoCheckout()
    {
        $this->mockGetCurrentCheckout();

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermNoQuoteDemand()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $checkout = $this->getMock(Checkout::class);

        $this->mockGetCurrentCheckout($checkout);
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

        $this->mockGetCurrentCheckout($checkout);
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

        $this->mockGetCurrentCheckout($checkout);
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

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn($checkoutSource);
        $checkoutSource->expects($this->exactly(2))->method('getQuote')->willReturn($quote);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertSame($paymentTerm, $this->event->getPaymentTerm());
    }

    /**
     * @param Checkout|null $checkout
     */
    protected function mockGetCurrentCheckout(Checkout $checkout = null)
    {
        $request = new Request();
        $request->attributes->set('_route', ResolvePaymentTermListener::CHECKOUT_ROUTE);
        $request->attributes->set('id', 1);
        $event = new CheckoutEntityEvent();
        $event->setCheckoutId(1);

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);
        $this
            ->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo(CheckoutEvents::GET_CHECKOUT_ENTITY),
                $this->logicalAnd(
                    $this->isInstanceOf(CheckoutEntityEvent::class),
                    $this->attributeEqualTo('checkoutId', 1)
                )
            )
            ->will($this->returnCallback(function ($eventName, CheckoutEntityEvent $event) use ($checkout) {
                $event->setCheckoutEntity($checkout);
            }));
    }
}
