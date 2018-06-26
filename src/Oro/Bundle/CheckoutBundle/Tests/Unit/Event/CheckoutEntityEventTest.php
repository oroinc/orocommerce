<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutEntityEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['checkoutEntity', new Checkout()],
            ['source', new CheckoutSource()],
            ['checkoutId', 12]
        ];

        $event = new CheckoutEntityEvent();
        $this->assertPropertyAccessors($event, $properties);
    }
}
