<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;

class CollectDependentClassesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testClassesAccessors()
    {
        $event = new CollectDependentClassesEvent(['Some\Class\Name']);
        $classes = $event->getClassesToResolve();
        $classes[] = 'Some\Dependent\Class';
        $classes[] = 'Some\Dependent\Class2';
        $event->setDependentClasses($classes);
        $expectation = ['Some\Class\Name', 'Some\Dependent\Class', 'Some\Dependent\Class2'];
        $this->assertEquals($expectation, array_values($event->getDependentClasses()));
    }
}
