<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\Model\Surcharge;

class CollectSurchargeEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var CollectSurchargeEvent */
    protected $event;

    /** @var object */
    protected $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new \stdClass();
        $this->event = new CollectSurchargeEvent($this->entity);
    }

    public function testGetEntity()
    {
        $this->assertSame($this->entity, $this->event->getEntity());
    }

    public function testGetSurchargeModel()
    {
        $this->assertInstanceOf(Surcharge::class, $this->event->getSurchargeModel());
    }
}
