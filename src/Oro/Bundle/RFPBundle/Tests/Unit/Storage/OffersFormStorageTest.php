<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Storage;

use Oro\Bundle\RFPBundle\Storage\OffersFormStorage;

class OffersFormStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var OffersFormStorage */
    private $storage;

    protected function setUp(): void
    {
        $this->storage = new OffersFormStorage();
    }

    /**
     * @dataProvider dataDataProvider
     */
    public function testGetData(array $storageData, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->storage->getData($storageData));
    }

    public function dataDataProvider(): array
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
     */
    public function testGetRawData(array $storageData, string $expectedData)
    {
        $this->assertEquals($expectedData, $this->storage->getRawData($storageData));
    }

    public function rawDataDataProvider(): array
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
