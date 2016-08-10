<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEvents;
use OroB2B\Bundle\CheckoutBundle\Provider\CheckoutProvider;

class CheckoutProviderTest extends \PHPUnit_Framework_TestCase
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
     * @var CheckoutProvider
     */
    protected $checkoutProvider;

    protected function setUp()
    {
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->checkoutProvider = new CheckoutProvider($this->requestStack, $this->eventDispatcher);
    }

    public function testGetCurrentNoRequest()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        $this->assertNull($this->checkoutProvider->getCurrent());
    }

    public function testGetCurrentNoRouteOnRequest()
    {
        $request = new Request();
        $request->attributes->set('_route', '');

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->assertNull($this->checkoutProvider->getCurrent());
    }

    public function testGetCurrent()
    {
        $request = new Request();
        $request->attributes->set('_route', CheckoutProvider::CHECKOUT_ROUTE);
        $request->attributes->set('id', 1);
        $checkout = new Checkout();
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

        $this->assertSame($checkout, $this->checkoutProvider->getCurrent());
    }
}
