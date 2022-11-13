<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\EventListener\PaymentTransactionListener;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

class PaymentTransactionListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentStatusManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var TransactionCompleteEvent */
    private $event;

    /** @var PaymentTransactionListener */
    private $listener;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(PaymentStatusManager::class);

        $this->event = new TransactionCompleteEvent(new PaymentTransaction());
        $this->listener = new PaymentTransactionListener($this->manager);
    }

    public function testOnTransactionComplete()
    {
        $transaction = $this->event->getTransaction();
        $this->manager->expects($this->once())
            ->method('updateStatus')
            ->with($transaction);
        $this->listener->onTransactionComplete($this->event);
    }
}
