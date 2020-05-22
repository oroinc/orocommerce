<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;

class MapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mapper
     */
    protected $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    protected function tearDown(): void
    {
        unset($this->mapper);
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

        $item = [
            'titleCode' => 'QWERTY',
            'codeId' => '123',
            'decimalField' => '12.34',
            'description' => 'I don\'t want to select it',
        ];

        $expectedResult = [
            'title' => 'QWERTY',
            'code' => 123,
            'decimalValue' => 12.34
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
}
