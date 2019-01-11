<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Event;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Event\DeclinedConsentsEvent;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\CustomerUserStub;

class DeclinedConsentsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCustomerUser()
    {
        $customerUser = new CustomerUserStub();
        $event = new DeclinedConsentsEvent([new ConsentAcceptance()], $customerUser);

        $this->assertSame($customerUser, $event->getCustomerUser());
    }

    public function testGetDeclinedConsents()
    {
        $declinedConsents = [new ConsentAcceptance()];
        $event = new DeclinedConsentsEvent($declinedConsents, new CustomerUserStub());

        $this->assertSame($declinedConsents, $event->getDeclinedConsents());
    }
}
