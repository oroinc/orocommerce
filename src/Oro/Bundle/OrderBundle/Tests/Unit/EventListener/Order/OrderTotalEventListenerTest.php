<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderTotalEventListener;
use Oro\Bundle\OrderBundle\Provider\TotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Symfony\Component\Form\FormInterface;

class OrderTotalEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use SubtotalTrait;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProvider;

    /** @var OrderTotalEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->totalProvider = $this->createMock(TotalProvider::class);

        $this->listener = new OrderTotalEventListener($this->totalProvider);
    }

    public function testOnOrderEvent()
    {
        $form = $this->createMock(FormInterface::class);

        $order = new Order();

        $total = $this->getSubtotal('type', 'label', 100, 'USD', true);

        $this->totalProvider->expects($this->once())
            ->method('getTotalWithSubtotalsWithBaseCurrencyValues')
            ->with($order)
            ->willReturn($total->toArray());

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);

        $actualData = $event->getData()->getArrayCopy();

        $this->assertArrayHasKey(OrderTotalEventListener::TOTALS_KEY, $actualData);
        $this->assertEquals($total->toArray(), $actualData[OrderTotalEventListener::TOTALS_KEY]);
    }
}
