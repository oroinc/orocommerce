<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ResolvePaymentTermListener;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ResolvePaymentTermListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ResolvePaymentTermEvent */
    private $event;

    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermProvider;

    /** @var ResolvePaymentTermListener */
    private $resolvePaymentTermListener;

    protected function setUp(): void
    {
        $this->event = new ResolvePaymentTermEvent();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);

        $this->resolvePaymentTermListener = new ResolvePaymentTermListener(
            $this->requestStack,
            $this->registry,
            $this->paymentTermProvider
        );
    }

    public function testOnResolvePaymentTermNoRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testGetCurrentNoRouteOnRequest()
    {
        $request = new Request();
        $request->attributes->set('_route', '');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

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
        $checkout = $this->createMock(Checkout::class);

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn(null);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermBadCheckoutSource()
    {
        $checkout = $this->createMock(Checkout::class);
        $checkoutSourceEntity = $this->createMock(CheckoutSourceEntityInterface::class);

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($checkoutSourceEntity);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTermNoPaymentTerm()
    {
        $checkout = $this->createMock(Checkout::class);
        $checkoutSourceEntity = new PaymentTermAwareStub();

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($checkoutSourceEntity);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertNull($this->event->getPaymentTerm());
    }

    public function testOnResolvePaymentTerm()
    {
        $checkout = $this->createMock(Checkout::class);
        $paymentTerm = new PaymentTerm();
        $checkoutSourceEntity = new PaymentTermAwareStub($paymentTerm);

        $this->mockGetCurrentCheckout($checkout);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($checkoutSourceEntity);

        $this->paymentTermProvider->expects($this->once())
            ->method('getObjectPaymentTerm')
            ->willReturn($paymentTerm);

        $this->resolvePaymentTermListener->onResolvePaymentTerm($this->event);
        $this->assertSame($paymentTerm, $this->event->getPaymentTerm());
    }

    private function mockGetCurrentCheckout(Checkout $checkout = null): void
    {
        $request = new Request();
        $request->attributes->set('_route', ResolvePaymentTermListener::CHECKOUT_ROUTE);
        $request->attributes->set('id', 42);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($this->manager);

        $this->manager->expects($this->once())
            ->method('find')
            ->with(Checkout::class, 42)
            ->willReturn($checkout);
    }
}
