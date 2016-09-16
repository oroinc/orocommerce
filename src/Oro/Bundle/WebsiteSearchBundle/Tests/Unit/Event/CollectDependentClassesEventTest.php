<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;

class CollectDependentClassesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetClassesForReindexWithEmptyArray()
    {
        $event = new CollectDependentClassesEvent();

        $this->assertEquals([], $event->getDependencies());
    }

    public function testGetClassesForReindexWithSimpleDependencies()
    {
        $event = new CollectDependentClassesEvent();

        $event->addClassDependencies('Product', ['Category', 'User']);

        $expectedDependencies = [
            'Category' => ['Product'],
            'User' => ['Product'],
        ];

        $this->assertEquals($expectedDependencies, $event->getDependencies());
    }

    public function testGetClassesForReindexWithCircularDependencies()
    {
        $event = new CollectDependentClassesEvent();

        $event->addClassDependencies('Product', ['Category']);
        $event->addClassDependencies('Category', ['SomeEntity']);
        $event->addClassDependencies('SomeEntity', ['Product']);

        $expectedDependencies = [
            'Category' => ['Product'],
            'SomeEntity' => ['Category'],
            'Product' => ['SomeEntity'],
        ];

        $this->assertEquals($expectedDependencies, $event->getDependencies());
    }
}
