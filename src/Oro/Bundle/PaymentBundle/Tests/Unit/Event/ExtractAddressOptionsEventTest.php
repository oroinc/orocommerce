<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;

class ExtractAddressOptionsEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtractAddressOptionsEvent */
    protected $event;

    /** @var object */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new Address();
        $this->event = new ExtractAddressOptionsEvent($this->entity);
    }

    public function testGetEntity()
    {
        $this->assertSame($this->entity, $this->event->getEntity());
    }

    public function testGetModel()
    {
        $this->assertInstanceOf(AddressOptionModel::class, $this->event->getModel());
    }
}
