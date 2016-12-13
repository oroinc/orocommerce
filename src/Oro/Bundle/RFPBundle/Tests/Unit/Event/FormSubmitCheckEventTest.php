<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Event;

use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;

class FormSubmitCheckEventTest extends \PHPUnit_Framework_TestCase
{
    public function testSubmitOnErrorHandlesCorrectly()
    {
        $event = new FormSubmitCheckEvent();
        $this->assertFalse($event->isSubmitOnError());
        $event->setShouldSubmitOnError(true);
        $this->assertTrue($event->isSubmitOnError());
    }
}
