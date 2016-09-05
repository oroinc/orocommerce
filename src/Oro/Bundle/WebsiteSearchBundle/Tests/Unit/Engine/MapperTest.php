<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;

class MapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mapper
     */
    protected $mapper;

    protected function setUp()
    {
        $this->mapper = new Mapper();
    }

    protected function tearDown()
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

    public function testMapSelectedDataEmptySelect()
    {
        $query = new Query();
        $item = [
            'title' => 'Test item title',
        ];

        $this->assertNull($this->mapper->mapSelectedData($query, $item));
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
