<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Event;

use Oro\Bundle\ApplicationBundle\Event\ModelEvent;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel;

class ModelEventTest extends \PHPUnit_Framework_TestCase
{
    public function testModelAccessors()
    {
        $sourceModel = new TestModel('source');
        $alteredModel = new TestModel('altered');

        $event = new ModelEvent($sourceModel);
        $this->assertEquals($sourceModel, $event->getModel());

        $event->setModel($alteredModel);
        $this->assertEquals($alteredModel, $event->getModel());
    }
}
