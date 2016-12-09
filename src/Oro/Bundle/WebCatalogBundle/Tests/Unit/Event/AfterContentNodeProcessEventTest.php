<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Event;

use Oro\Bundle\WebCatalogBundle\Event\AfterContentNodeProcessEvent;
use Symfony\Component\Form\Test\FormInterface;

class AfterContentNodeProcessEventTest extends \PHPUnit_Framework_TestCase
{
    public function testSettersGetters()
    {
        $formInterface = $this->getMock(FormInterface::class);
        $object = new \stdClass();

        $afterContentNodeProcessEvent = new AfterContentNodeProcessEvent($formInterface, $object);

        $this->assertEquals($afterContentNodeProcessEvent->getData(), $object);
        $this->assertEquals($afterContentNodeProcessEvent->getForm(), $formInterface);
    }
}
