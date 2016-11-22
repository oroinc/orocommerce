<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Event;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;

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
