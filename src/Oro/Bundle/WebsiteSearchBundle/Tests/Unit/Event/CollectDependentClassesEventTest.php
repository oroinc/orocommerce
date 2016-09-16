<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;

class CollectDependentClassesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetClassesForReindexWhenNoDependenciesAdded()
    {
        $event = new CollectDependentClassesEvent();

        $classes = ['Product', 'Category'];

        $this->assertEquals($classes, $event->getClassesForReindex($classes));
    }

    public function testGetClassesForReindexWithEmptyArray()
    {
        $event = new CollectDependentClassesEvent();

        $this->assertEquals([], $event->getClassesForReindex([]));
    }

    public function testGetClassesForReindexWithSimpleDependencies()
    {
        $event = new CollectDependentClassesEvent();

        $event->addClassDependencies('Product', ['Category', 'User']);

        $this->assertEquals(['Category', 'Product'], $event->getClassesForReindex(['Category']));
        $this->assertEquals(['User', 'Product'], $event->getClassesForReindex(['User']));
        $this->assertEquals(['Product'], $event->getClassesForReindex(['Product']));
    }

    public function testGetClassesForReindexWithCircularDependencies()
    {
        $event = new CollectDependentClassesEvent();

        $event->addClassDependencies('Product', ['Category']);
        $event->addClassDependencies('Category', ['SomeEntity']);
        $event->addClassDependencies('SomeEntity', ['Product']);

        $this->assertEquals(['Category', 'Product', 'SomeEntity'], $event->getClassesForReindex(['Category']));
        $this->assertEquals(['Product', 'SomeEntity', 'Category'], $event->getClassesForReindex(['Product']));
        $this->assertEquals(['SomeEntity', 'Category', 'Product'], $event->getClassesForReindex(['SomeEntity']));
    }
}
