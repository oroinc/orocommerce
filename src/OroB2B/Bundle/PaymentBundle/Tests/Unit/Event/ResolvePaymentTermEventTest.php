<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;

class ResolvePaymentTermEventTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [['paymentTerm', new PaymentTerm()]];
        $event = new ResolvePaymentTermEvent();
        $this->assertPropertyAccessors($event, $properties);
    }
}
