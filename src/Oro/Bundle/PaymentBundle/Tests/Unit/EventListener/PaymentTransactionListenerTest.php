<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\EventListener\PaymentTransactionListener;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

class PaymentTransactionListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentStatusManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $managerMock;

    /** @var TransactionCompleteEvent */
    protected $event;

    /** @var PaymentTransactionListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->managerMock = $this->getMockBuilder(PaymentStatusManager::class)
            ->disableOriginalConstructor()->getMock();

        $transaction = new PaymentTransaction();
        $this->event = new TransactionCompleteEvent($transaction);
        $this->listener = new PaymentTransactionListener($this->managerMock);
    }

    public function testOnTransactionComplete()
    {
        $transaction = $this->event->getTransaction();
        $this->managerMock->expects($this->once())->method('updateStatus')->with($transaction);
        $this->listener->onTransactionComplete($this->event);
    }
}
