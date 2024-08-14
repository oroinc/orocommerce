<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ActualizeCurrencyInterface;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\CheckoutRequestActualizeCurrencyListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CheckoutRequestActualizeCurrencyListenerTest extends TestCase
{
    private ActualizeCurrencyInterface|MockObject $actualizeCurrency;
    private CheckoutRequestActualizeCurrencyListener $listener;

    protected function setUp(): void
    {
        $this->actualizeCurrency = $this->createMock(ActualizeCurrencyInterface::class);
        $this->listener = new CheckoutRequestActualizeCurrencyListener(
            $this->actualizeCurrency
        );
    }

    public function testOnCheckoutRequest(): void
    {
        $request = $this->createMock(Request::class);
        $checkout = $this->createMock(Checkout::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->actualizeCurrency->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->listener->onCheckoutRequest($event);
    }
}
