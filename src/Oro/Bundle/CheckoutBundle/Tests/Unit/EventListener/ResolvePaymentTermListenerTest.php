<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEvents;
use Oro\Bundle\CheckoutBundle\EventListener\ResolvePaymentTermListener;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @var ResolvePaymentTermEvent
     */
    protected $event;

    /**
     * @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTermProvider;

    /**
     * @var ResolvePaymentTermListener
     */
    protected $resolvePaymentTermListener;

    protected function setUp()
    {
        $this->event = new ResolvePaymentTermEvent();
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->paymentTermProvider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->resolvePaymentTermListener = new ResolvePaymentTermListener(
            $this->requestStack,
            $this->eventDispatcher,
            $this->paymentTermProvider
        );
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

    public function testOnResolvePaymentTermEntityDemand()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout */
        $checkout = $this->getMock(Checkout::class);

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn(null);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermBadCheckoutSource()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout */
        $checkout = $this->getMock(Checkout::class);
        /** @var CheckoutSourceEntityInterface|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $checkoutSourceEntity = $this->getMock(CheckoutSourceEntityInterface::class);

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn($checkoutSourceEntity);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermNoPaymentTerm()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout */
        $checkout = $this->getMock(Checkout::class);
        $checkoutSourceEntity = new PaymentTermAwareStub();

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn($checkoutSourceEntity);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTerm()
    {
        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout */
        $checkout = $this->getMock(Checkout::class);
        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $paymentTerm = new PaymentTerm();
        $checkoutSourceEntity = new PaymentTermAwareStub($paymentTerm);

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())->method('getSourceEntity')->willReturn($checkoutSourceEntity);

        $this->paymentTermProvider->expects($this->once())->method('getObjectPaymentTerm')->willReturn($paymentTerm);

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
            ->will(
                $this->returnCallback(
                    function ($eventName, CheckoutEntityEvent $event) use ($checkout) {
                        $event->setCheckoutEntity($checkout);
                    }
                )
            );
    }
}
