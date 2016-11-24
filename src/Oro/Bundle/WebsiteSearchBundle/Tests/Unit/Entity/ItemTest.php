<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Entity;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var Item */
    protected $item;

    protected function setUp()
    {
        $this->item = new Item();
    }

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['entity', 'Some\Entity'],
            ['alias', 'some_entity_alias'],
            ['title', 'Some text is here'],
            ['recordId', 1],
            ['changed', false],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()]
        ];
        $this->assertPropertyAccessors($this->item, $properties);
        $propertyCollection = [
            ['textFields', new IndexText()],
            ['integerFields', new IndexInteger()],
            ['decimalFields', new IndexDecimal()],
            ['datetimeFields', new IndexDatetime()],
        ];
        $this->assertPropertyCollections($this->item, $propertyCollection);
    }

    public function testBeforeSave()
    {
        $this->item->beforeSave();
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $createdAt = $this->item->getCreatedAt();
        $this->assertEquals($date->format('Y-m-d'), $createdAt->format('Y-m-d'));
        $updatedAt = $this->item->getUpdatedAt();
        $this->assertEquals($date->format('Y-m-d'), $updatedAt->format('Y-m-d'));
    }

    public function testBeforeUpdate()
    {
        $this->item->beforeUpdate();
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $updatedAt = $this->item->getUpdatedAt();
        $this->assertEquals($date->format('Y-m-d'), $updatedAt->format('Y-m-d'));
    }

    public function testSaveItemData()
    {
        $this->item->saveItemData(
            [
                'text' => [
                    'test_field' => 'test text'
                ],
                'integer' => [
                    'test_integer' => 10,
                    'test_integer_array' => [2, 3]
                ],
                'datetime' => [
                    'test_datetime' => new \DateTime('2013-01-01')
                ],
                'decimal' => [
                    'test_decimal' => 10.26
                ]
            ]
        );

        $textFields = $this->item->getTextFields();
        $this->assertEquals('test text', $textFields->get(0)->getValue());
        $integerFields = $this->item->getIntegerFields();
        $this->assertEquals(3, $integerFields->count());
        $this->assertEquals(10, $integerFields->get(0)->getValue());
        $this->assertEquals(2, $integerFields->get(1)->getValue());
        $this->assertEquals(3, $integerFields->get(2)->getValue());
        $datetimeFields = $this->item->getDatetimeFields();
        $this->assertEquals('2013-01-01', $datetimeFields->get(0)->getValue()->format('Y-m-d'));
        $decimalFields = $this->item->getDecimalFields();
        $this->assertEquals(10.26, $decimalFields->get(0)->getValue());

        $this->item->saveItemData(
            [
                'integer' => [
                    'test_integer' => 10,
                    'test_integer_array' => [5]
                ],
            ]
        );

        $integerFields = $this->item->getIntegerFields();
        $this->assertEquals(2, $integerFields->count());
        $this->assertEquals(5, $integerFields->get(3)->getValue());
    }
}
