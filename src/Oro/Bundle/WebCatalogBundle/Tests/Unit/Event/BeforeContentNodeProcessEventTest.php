<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Event;

use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\WebCatalogBundle\Event\BeforeContentNodeProcessEvent;

class BeforeContentNodeProcessEventTest extends \PHPUnit_Framework_TestCase
{
    public function testSettersGetters()
    {
        $formInterface = $this->getMock(FormInterface::class);
        $object = new \stdClass();

        $afterContentNodeProcessEvent = new BeforeContentNodeProcessEvent($formInterface, $object);

        $this->assertFalse($afterContentNodeProcessEvent->isFormProcessInterrupted());

        $afterContentNodeProcessEvent->interruptFormProcess();

        $this->assertEquals($afterContentNodeProcessEvent->getData(), $object);
        $this->assertEquals($afterContentNodeProcessEvent->getForm(), $formInterface);
        $this->assertTrue($afterContentNodeProcessEvent->isFormProcessInterrupted());
    }
}