<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\SearchBundle\Query\Query;

class IndexEntityEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntityName()
    {
        $event = new IndexEntityEvent('EntityName', [], []);

        $this->assertEquals('EntityName', $event->getEntityName());
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

    public function testGetEntityDataWhenNothingWasAdded()
    {
        $event = new IndexEntityEvent('', [], []);

        $this->assertEquals([], $event->getEntityData(1));
    }

    public function testGetEntityDataWhenFieldsAreAdded()
    {
        $event = new IndexEntityEvent('', [1, 2], []);

        $event->addField(1, Query::TYPE_TEXT, 'title', 'Product title');
        $event->addField(1, Query::TYPE_TEXT, 'description', 'Product description');
        $event->addField(1, Query::TYPE_DECIMAL, 'price', 100.00);
        $event->addField(1, Query::TYPE_INTEGER, 'categoryId', 3);
        $event->addField(2, Query::TYPE_TEXT, 'title', 'Another product title');

        $expectedDataForFirstEntity = [
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
        ];

        $expectedDataForSecondEntity = [
            Query::TYPE_TEXT => [
                'title' => 'Another product title'
            ]
        ];

        $this->assertEquals($expectedDataForFirstEntity, $event->getEntityData(1));
        $this->assertEquals($expectedDataForSecondEntity, $event->getEntityData(2));
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
