<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use OroB2B\Bundle\PaymentBundle\EventListener\PaymentTransactionListener;
use OroB2B\Bundle\PaymentBundle\Manager\PaymentStatusManager;

class PaymentTransactionListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentStatusManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $managerMock;

    /** @var TransactionCompleteEvent */
    protected $event;

    /** @var PaymentTransactionListener */
    protected $listener;

    protected function setUp()
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
