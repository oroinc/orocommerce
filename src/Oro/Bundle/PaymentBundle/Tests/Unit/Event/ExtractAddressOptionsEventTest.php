<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;

class ExtractAddressOptionsEventTest extends AbstractExtractOptionsEventTestCase
{
    /** @var ExtractAddressOptionsEvent */
    protected $event;

    /** @var object */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new \stdClass();
        $this->event = new ExtractAddressOptionsEvent($this->entity, $this->keys);
    }

    public function testGetEntity()
    {
        $this->assertSame($this->entity, $this->event->getEntity());
    }
}
