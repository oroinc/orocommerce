<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Storage;

use Oro\Bundle\RFPBundle\Storage\OffersFormStorage;
use PHPUnit\Framework\TestCase;

class OffersFormStorageTest extends TestCase
{
    private OffersFormStorage $storage;

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
                ['offers_data' => json_encode([['productId' => 42, 'qty' => 100]])],
                [['productId' => 42, 'qty' => 100]],
            ],
            [
                [
                    'offers_data' => json_encode(
                        [['productId' => 42, 'qty' => 100], ['productId' => 43, 'qty' => 101]]
                    ),
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
    public function testGetRawData(array $storageData)
    {
        $this->assertEquals(json_encode($storageData), $this->storage->getRawData($storageData));
    }

    public function rawDataDataProvider(): array
    {
        return [
            [[null]],
            [['test']],
            [[10]],
            [[['productId' => 42, 'qty' => 100]]],
            [[['productId' => 42, 'qty' => 100], ['productId' => 43, 'qty' => 101]]],
            [[]],
        ];
    }
}
