<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Event;

use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Event\AfterContentNodeProcessEvent;

class AfterContentNodeProcessEventTest extends \PHPUnit_Framework_TestCase
{
    public function testSettersGetters()
    {
        $formInterface = $this->getMock(FormInterface::class);
        $contentNode = new ContentNode();

        $afterContentNodeProcessEvent = new AfterContentNodeProcessEvent($formInterface, $contentNode);

        $this->assertEquals($afterContentNodeProcessEvent->getContentNode(), $contentNode);
        $this->assertEquals($afterContentNodeProcessEvent->getForm(), $formInterface);
    }
}
