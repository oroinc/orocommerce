<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;

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
