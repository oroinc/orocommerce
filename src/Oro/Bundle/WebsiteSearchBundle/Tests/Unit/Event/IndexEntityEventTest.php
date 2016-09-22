<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\SearchBundle\Query\Query;

class IndexEntityEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntityName()
    {
        $event = new IndexEntityEvent('EntityName', [], []);

        $this->assertEquals('EntityName', $event->getEntityClass());
    }

    public function testGetEntityIds()
    {
        $entityIds = [1, 2, 3];
        $event = new IndexEntityEvent('', $entityIds, []);

        $this->assertEquals($entityIds, $event->getEntityIds());
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

        $event->addField(1, Query::TYPE_TEXT, 'title', 'Product title');
        $event->addField(1, Query::TYPE_TEXT, 'description', 'Product description');
        $event->addField(1, Query::TYPE_DECIMAL, 'price', 100.00);
        $event->addField(1, Query::TYPE_INTEGER, 'categoryId', 3);
        $event->addField(2, Query::TYPE_TEXT, 'title', 'Another product title');

        $expectedData = [
            1 => [
                Query::TYPE_TEXT => [
                    'title' => 'Product title',
                    'description' => 'Product description'
                ],
                Query::TYPE_DECIMAL => [
                    'price' => 100.00
                ],
                Query::TYPE_INTEGER => [
                    'categoryId' => 3
                ]
            ],
            2 => [
                Query::TYPE_TEXT => [
                    'title' => 'Another product title'
                ]
            ]
        ];

        $this->assertEquals($expectedData, $event->getEntitiesData());
    }

    public function testAppendingValuesToFields()
    {
        $event = new IndexEntityEvent('', [1, 2], []);

        $event->addField(1, Query::TYPE_TEXT, 'all_text', 'Product title');
        $event->addField(1, Query::TYPE_TEXT, 'all_text', ' MetaTitle', true);

        $expectedData = [
            1 => [
                Query::TYPE_TEXT => [
                    'all_text' => 'Product title MetaTitle',
                ]
            ]
        ];

        $this->assertEquals($expectedData, $event->getEntitiesData());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Field type must be one of datetime, decimal, integer, text
     */
    public function testAddFieldWithWrongFieldType()
    {
        $event = new IndexEntityEvent('', [1], []);

        $event->addField(1, 'wrongType', 'title', 'Product title');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There is no entity with id 999
     */
    public function testAddFieldWithWrongEntityId()
    {
        $event = new IndexEntityEvent('', [1], []);

        $event->addField(999, 'wrongType', 'title', 'Product title');
    }
}
