<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

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
        $this->entity = new \stdClass();
        $this->event = new ExtractAddressOptionsEvent($this->entity);
    }

    public function testGetEntity()
    {
        $this->assertSame($this->entity, $this->event->getEntity());
    }

    public function testGetAndSetModel()
    {
        $addressModel = new AddressOptionModel();
        $this->event->setModel($addressModel);
        $this->assertSame($addressModel, $this->event->getModel());
    }

    public function testGetDefaultModel()
    {
        $this->assertInstanceOf(AddressOptionModel::class, $this->event->getModel());
    }
}
