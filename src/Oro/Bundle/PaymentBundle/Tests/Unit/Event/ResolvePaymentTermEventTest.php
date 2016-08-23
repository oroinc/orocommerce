<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;

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
