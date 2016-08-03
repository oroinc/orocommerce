<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Event;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;

class CheckoutEntityEventTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['checkoutEntity', new Checkout()],
            ['source', new CheckoutSource()],
            ['checkoutId', 12],
            ['type', 'type'],
            ['workflowName', 'workflowName']
        ];

        $event = new CheckoutEntityEvent();
        $this->assertPropertyAccessors($event, $properties);
    }
}
