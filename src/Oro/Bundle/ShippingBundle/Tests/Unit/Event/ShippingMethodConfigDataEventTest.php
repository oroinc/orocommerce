<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Event;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingMethodConfigDataEventTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['methodIdentifier', 'method_1', false],
            ['template', 'Some:template.html.twig', false],
        ];

        $event = new ShippingMethodConfigDataEvent('method_1');
        $this->assertPropertyAccessors($event, $properties);
    }
}
