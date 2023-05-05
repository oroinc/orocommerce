<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;

class MapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var Mapper */
    private $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper(new DateTimeFormatter());
    }

    public function testMapSelectedData()
    {
        $query = new Query();
        $query->select('title');
        $query->addSelect('codes');

        $item = [
            'title' => 'Test item title',
            'codes' => [
                'code1',
                'code2',
            ],
            'description' => 'I don\'t want to select it',
        ];

        $expectedResult = [
            'title' => 'Test item title',
            'codes' => 'code1',
        ];

        $this->assertEquals($expectedResult, $this->mapper->mapSelectedData($query, $item));
    }

    public function testMapSelectedDataWithAliases()
    {
        $query = new Query();
        $query->select('titleCode as title');
        $query->addSelect('integer.codeId as code');
        $query->addSelect('decimal.decimalField as decimalValue');
        $query->addSelect('datetime.updated as updatedAt');

        $item = [
            'titleCode' => 'QWERTY',
            'codeId' => '123',
            'decimalField' => '12.34',
            'updated' => new \DateTime('2022-12-12 12:13:14', new \DateTimeZone('UTC')),
            'description' => 'I don\'t want to select it',
        ];

        $expectedResult = [
            'title' => 'QWERTY',
            'code' => 123,
            'decimalValue' => 12.34,
            'updatedAt' => '2022-12-12 12:13:14',
        ];

        $this->assertSame($expectedResult, $this->mapper->mapSelectedData($query, $item));
    }

    public function testMapSelectedDataEmptySelect()
    {
        $query = new Query();
        $item = [
            'title' => 'Test item title',
        ];

        $this->assertEquals([], $this->mapper->mapSelectedData($query, $item));
    }

    public function testMapSelectedDataEmptyItem()
    {
        $query = new Query();
        $query->select('title');
        $query->addSelect('codes');

        $item = [];

        $expectedResult = [
            'title' => '',
            'codes' => '',
        ];

        $this->assertEquals($expectedResult, $this->mapper->mapSelectedData($query, $item));
    }

    public function testMapSelectedDataWithFlatFields()
    {
        $query = new Query();
        $query->addSelect('integer.category_paths.1_2');
        $query->addSelect('integer.category_paths.1_3 as third_category');
        $query->addSelect('integer.category_paths.1_4 as fourth_category');
        $query->addSelect('integer.category_paths.1_9 as missing_category');

        $item1 = [
            'category_paths.1_2' => '2',
            'category_paths.1_3' => 3,
            'category_paths.1_4' => 4,
        ];
        $item2 = [
            'category_paths__SEPARATOR__1_2' => '2',
            'category_paths__SEPARATOR__1_3' => 3,
            'category_paths__SEPARATOR__1_4' => 4,
        ];
        $item3 = [
            'category_paths' => [
                '1_2' => '2',
                '1_3' => 3,
                '1_4' => 4,
            ],
        ];

        $expectedResult = [
            'category_paths.1_2' => 2,
            'third_category' => 3,
            'fourth_category' => 4,
            'missing_category' => '',
        ];

        $this->assertSame($expectedResult, $this->mapper->mapSelectedData($query, $item1));
        $this->assertSame($expectedResult, $this->mapper->mapSelectedData($query, $item2));
        $this->assertSame($expectedResult, $this->mapper->mapSelectedData($query, $item3));
    }
}
