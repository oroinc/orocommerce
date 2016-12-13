<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;

class IndexEntityEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntityIds()
    {
        $entities = [new \stdClass(), new \stdClass()];
        $event = new IndexEntityEvent($entities, []);

        $this->assertEquals($entities, $event->getEntities());
    }

    public function testGetContext()
    {
        $context = [
            'someKey' => 'someValue',
        ];

        $event = new IndexEntityEvent([], $context);

        $this->assertEquals($context, $event->getContext());
    }

    public function testGetEntitiesDataWhenNothingWasAdded()
    {
        $event = new IndexEntityEvent([], []);

        $this->assertEquals([], $event->getEntitiesData());
    }

    public function testGetEntityDataWhenFieldsAreAdded()
    {
        $event = new IndexEntityEvent([1, 2], []);

        $event->addField(1, 'name', 'Product name');
        $event->addField(1, 'description', 'Product description');
        $event->addField(1, 'price', 100.00);
        $event->addField(1, 'categoryId', 3);
        $event->addField(2, 'name', 'Another product name');
        $date = new \DateTime();
        $event->addField(2, 'date', $date);

        $expectedData = [
            1 => [
                'name' => 'Product name',
                'description' => 'Product description',
                'price' => 100.00,
                'categoryId' => 3,
            ],
            2 => [
                'name' => 'Another product name',
                'date' => $date,
            ],
        ];

        $this->assertEquals($expectedData, $event->getEntitiesData());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Scalars and \DateTime are supported only, "stdClass" given
     */
    public function testAddFieldObject()
    {
        $event = new IndexEntityEvent([], []);
        $event->addField(1, 'sku', new \stdClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Scalars and \DateTime are supported only, "array" given
     */
    public function testAddFieldArray()
    {
        $event = new IndexEntityEvent([], []);
        $event->addField(1, 'sku', []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage supported only, "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue" given
     */
    public function testAddPlaceholderFieldNotSupported()
    {
        $event = new IndexEntityEvent([], []);
        $event->addPlaceholderField(1, 'sku', new LocalizedFallbackValue(), []);
    }

    public function testSamePlaceholderValueDoestOverridePrevious()
    {
        $event = new IndexEntityEvent([], []);
        $event->addPlaceholderField(1, 'sku', 'value1', []);
        $event->addPlaceholderField(1, 'sku', 'value2', []);

        $this->assertEquals(
            [1 => ['sku' => [new PlaceholderValue('value1'), new PlaceholderValue('value2')]]],
            $event->getEntitiesData()
        );
    }
}
