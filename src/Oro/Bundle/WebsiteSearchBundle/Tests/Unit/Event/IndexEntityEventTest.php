<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Provider\IndexDataProvider;

class IndexEntityEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntityName()
    {
        $event = new IndexEntityEvent('EntityName', [], []);

        $this->assertEquals('EntityName', $event->getEntityClass());
    }

    public function testGetEntityIds()
    {
        $entities = [new \stdClass(), new \stdClass()];
        $event = new IndexEntityEvent('', $entities, []);

        $this->assertEquals($entities, $event->getEntities());
    }

    public function testGetContext()
    {
        $context = [
            'someKey' => 'someValue'
        ];

        $event = new IndexEntityEvent('', [], $context);

        $this->assertEquals($context, $event->getContext());
    }

    public function testGetEntitiesDataWhenNothingWasAdded()
    {
        $event = new IndexEntityEvent('', [], []);

        $this->assertEquals([], $event->getEntitiesData());
    }

    public function testGetEntityDataWhenFieldsAreAdded()
    {
        $event = new IndexEntityEvent('', [1, 2], []);

        $event->addField(1, 'title', 'Product title');
        $event->addField(1, 'description', 'Product description');
        $event->addField(1, 'price', 100.00);
        $event->addField(1, 'categoryId', 3);
        $event->addField(2, 'title', 'Another product title');

        $expectedData = [
            1 => [
                IndexDataProvider::STANDARD_VALUES_KEY => [
                    'title' => 'Product title',
                    'description' => 'Product description',
                    'price' => 100.00,
                    'categoryId' => 3
                ]
            ],
            2 => [
                IndexDataProvider::STANDARD_VALUES_KEY => [
                    'title' => 'Another product title'
                ]
            ]
        ];

        $this->assertEquals($expectedData, $event->getEntitiesData());
    }
}
