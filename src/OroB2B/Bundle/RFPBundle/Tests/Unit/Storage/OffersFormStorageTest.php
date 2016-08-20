<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Storage;

use Oro\Bundle\RFPBundle\Storage\OffersFormStorage;

class OffersFormStorageTest extends \PHPUnit_Framework_TestCase
{
    /** @var OffersFormStorage */
    protected $storage;

    protected function setUp()
    {
        $this->storage = new OffersFormStorage();
    }

    /**
     * @dataProvider dataDataProvider
     *
     * @param array $storageData
     * @param array $expectedData
     */
    public function testGetData(array $storageData, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->storage->getData($storageData));
    }

    /**
     * @return array
     */
    public function dataDataProvider()
    {
        return [
            [[], []],
            [['test' => 'test'], []],
            [['offers_data' => 'test'], []],
            [['offers_data' => 10], []],
            [
                ['offers_data' => 'a:1:{i:0;a:2:{s:9:"productId";i:42;s:3:"qty";i:100;}}'],
                [['productId' => 42, 'qty' => 100]],
            ],
            [
                [
                    'offers_data' => 'a:2:{i:0;a:2:{s:9:"productId";i:42;s:3:"qty";i:100;}' .
                        'i:1;a:2:{s:9:"productId";i:43;s:3:"qty";i:101;}}',
                ],
                [['productId' => 42, 'qty' => 100], ['productId' => 43, 'qty' => 101]],
            ],
            [['offers_data' => '[{invalid_json:100}]'], []],
            [['offers_data' => false], []],
            [['offers_data' => ''], []],
            [['offers_data' => '[]'], []],
        ];
    }

    /**
     * @dataProvider rawDataDataProvider
     *
     * @param array $storageData
     * @param string $expectedData
     */
    public function testGetRawData(array $storageData, $expectedData)
    {
        $this->assertEquals($expectedData, $this->storage->getRawData($storageData));
    }

    /**
     * @return array
     */
    public function rawDataDataProvider()
    {
        return [
            [[null], 'a:1:{i:0;N;}'],
            [['test'], 'a:1:{i:0;s:4:"test";}'],
            [[10], 'a:1:{i:0;i:10;}'],
            [[['productId' => 42, 'qty' => 100]], 'a:1:{i:0;a:2:{s:9:"productId";i:42;s:3:"qty";i:100;}}'],
            [
                [['productId' => 42, 'qty' => 100], ['productId' => 43, 'qty' => 101]],
                'a:2:{i:0;a:2:{s:9:"productId";i:42;s:3:"qty";i:100;}i:1;a:2:{s:9:"productId";i:43;s:3:"qty";i:101;}}',
            ],
            [[], 'a:0:{}'],
        ];
    }
}
