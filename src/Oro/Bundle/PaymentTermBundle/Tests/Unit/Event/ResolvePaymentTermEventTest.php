<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ResolvePaymentTermEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [['paymentTerm', new PaymentTerm()]];
        $event = new ResolvePaymentTermEvent();
        $this->assertPropertyAccessors($event, $properties);
    }
}
