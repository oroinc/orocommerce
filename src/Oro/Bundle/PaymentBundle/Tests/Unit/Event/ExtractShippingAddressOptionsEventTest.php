<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Event\ExtractShippingAddressOptionsEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class ExtractShippingAddressOptionsEventTest extends AbstractExtractOptionsEventTestCase
{
    /** @var ExtractShippingAddressOptionsEvent */
    protected $event;

    /** @var object */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new \stdClass();
        $this->event = new ExtractShippingAddressOptionsEvent($this->entity, $this->keys);
    }

    public function testGetEntity()
    {
        $this->assertSame($this->entity, $this->event->getEntity());
    }
}
