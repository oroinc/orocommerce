<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\TransactionCompleteEvent;

class TransactionCompleteEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTransaction()
    {
        $transaction = new PaymentTransaction();
        $event = new TransactionCompleteEvent($transaction);
        $result = $event->getTransaction();
        $this->assertSame($transaction, $result);
    }
}
