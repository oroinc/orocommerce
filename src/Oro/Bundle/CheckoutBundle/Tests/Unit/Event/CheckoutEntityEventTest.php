<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class CheckoutEntityEventTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['checkoutEntity', new Checkout()],
            ['source', new CheckoutSource()],
            ['checkoutId', 12]
        ];

        $event = new CheckoutEntityEvent();
        self::assertPropertyAccessors($event, $properties);
    }

    public function testAllPropertiesAreNull(): void
    {
        $properties = [
            ['checkoutEntity', null],
            ['source', null],
            ['checkoutId', null]
        ];

        $event = new CheckoutEntityEvent();
        self::assertPropertyAccessors($event, $properties);
    }
}
