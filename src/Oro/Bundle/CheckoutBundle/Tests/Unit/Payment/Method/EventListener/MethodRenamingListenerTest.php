<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Payment\Method\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Payment\Method\EventListener\MethodRenamingListener;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;

class MethodRenamingListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckoutRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutRepository;

    /**
     * @var MethodRenamingListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->listener = new MethodRenamingListener($this->checkoutRepository);
    }

    public function testOnMethodRename()
    {
        $oldId = 'old_name';
        $newId = 'new_name';

        /** @var MethodRenamingEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(MethodRenamingEvent::class);
        $event->expects(static::once())
            ->method('getOldMethodIdentifier')
            ->willReturn($oldId);

        $event->expects(static::once())
            ->method('getNewMethodIdentifier')
            ->willReturn($newId);

        $checkout1 = $this->createMock(Checkout::class);
        $checkout1->expects(static::once())
            ->method('setPaymentMethod')
            ->with($newId);
        $checkout2 = $this->createMock(Checkout::class);
        $checkout2->expects(static::once())
            ->method('setPaymentMethod')
            ->with($newId);

        $this->checkoutRepository->expects(static::once())
            ->method('findByPaymentMethod')
            ->with($oldId)
            ->willReturn([$checkout1, $checkout2]);

        $this->listener->onMethodRename($event);
    }
}
