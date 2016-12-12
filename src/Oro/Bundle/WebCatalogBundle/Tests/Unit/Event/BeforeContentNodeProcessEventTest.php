<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Event;

use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Event\BeforeContentNodeProcessEvent;

class BeforeContentNodeProcessEventTest extends \PHPUnit_Framework_TestCase
{
    public function testSettersGetters()
    {
        $formInterface = $this->getMock(FormInterface::class);
        $contentNode = new ContentNode();

        $afterContentNodeProcessEvent = new BeforeContentNodeProcessEvent($formInterface, $contentNode);

        $this->assertFalse($afterContentNodeProcessEvent->isFormProcessInterrupted());

        $afterContentNodeProcessEvent->interruptFormProcess();

        $this->assertEquals($afterContentNodeProcessEvent->getContentNode(), $contentNode);
        $this->assertEquals($afterContentNodeProcessEvent->getForm(), $formInterface);
        $this->assertTrue($afterContentNodeProcessEvent->isFormProcessInterrupted());
    }
}
