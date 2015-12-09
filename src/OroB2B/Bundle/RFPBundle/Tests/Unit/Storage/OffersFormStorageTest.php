<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Storage;

use OroB2B\Bundle\RFPBundle\Storage\OffersFormStorage;

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
            [['offers_data' => 10], [10]],
            [['offers_data' => '[{"productId":42,"qty":100}]'], [['productId' => 42, 'qty' => 100]]],
            [
                ['offers_data' => '[{"productId":42,"qty":100}, {"productId":43,"qty":101}]'],
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
            [[null], '[null]'],
            [['test'], '["test"]'],
            [[10], '[10]'],
            [[['productId' => 42, 'qty' => 100]], '[{"productId":42,"qty":100}]'],
            [
                [['productId' => 42, 'qty' => 100], ['productId' => 43, 'qty' => 101]],
                '[{"productId":42,"qty":100},{"productId":43,"qty":101}]',
            ],
            [[], '[]'],
        ];
    }
}
