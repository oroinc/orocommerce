<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Event;

use Oro\Bundle\ApplicationBundle\Event\ModelIdentifierEvent;

class ModelIdentifierEventTest extends \PHPUnit_Framework_TestCase
{
    public function testModelAccessors()
    {
        $sourceIdentifier = 1;
        $alteredIdentifier = 2;

        $event = new ModelIdentifierEvent($sourceIdentifier);
        $this->assertEquals($sourceIdentifier, $event->getIdentifier());

        $event->setIdentifier($alteredIdentifier);
        $this->assertEquals($alteredIdentifier, $event->getIdentifier());
    }
}
