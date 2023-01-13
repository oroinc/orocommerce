<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Migrations;

use Oro\Bundle\TaxBundle\Migrations\ZipCodeRangeHelper;

class ZipCodeRangeHelperTest extends \PHPUnit\Framework\TestCase
{
    private const JURISDICTION_ID = 1;
    private const TIME = 'time';

    /** @var ZipCodeRangeHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new ZipCodeRangeHelper();
    }

    /**
     * @dataProvider zipCodesDataProvider
     */
    public function testZipCodes(array $zipCodes, array $expectedData)
    {
        $data = [];

        $this->helper->extractZipCodeRanges($data, $zipCodes, self::JURISDICTION_ID, self::TIME);

        $this->assertEquals($expectedData, $data);
    }

    public function zipCodesDataProvider(): array
    {
        return [
            [
                ['100', '102', '103'],
                [
                    [self::JURISDICTION_ID, '100', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, null, '102', '103', self::TIME, self::TIME],
                ],
            ],
            [
                ['100', '102', '103', '104'],
                [
                    [self::JURISDICTION_ID, '100', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, null, '102', '104', self::TIME, self::TIME],
                ],
            ],
            [
                ['100', '102', '103', '104', '120'],
                [
                    [self::JURISDICTION_ID, '100', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, null, '102', '104', self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '120', null, null, self::TIME, self::TIME],
                ],
            ],
            [
                ['90', '91', '92', '100', '102', '103', '104', '120'],
                [
                    [self::JURISDICTION_ID, null, '90', '92', self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '100', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, null, '102', '104', self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '120', null, null, self::TIME, self::TIME],
                ],
            ],
            [
                ['90', '91', '92', '100', '102', '103', '104', '120', '130', '131'],
                [
                    [self::JURISDICTION_ID, null, '90', '92', self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '100', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, null, '102', '104', self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '120', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, null, '130', '131', self::TIME, self::TIME],
                ],
            ],
            [
                ['91', '92', '93', '94', '95', '96', '97', '98', '99', '100'],
                [
                    [self::JURISDICTION_ID, null, '91', '100', self::TIME, self::TIME],
                ],
            ],
            [
                ['91', '93', '95', '97', '99'],
                [
                    [self::JURISDICTION_ID, '91', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '93', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '95', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '97', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '99', null, null, self::TIME, self::TIME],
                ],
            ],
            [
                ['99', '97', '95', '93', '91'],
                [
                    [self::JURISDICTION_ID, '91', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '93', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '95', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '97', null, null, self::TIME, self::TIME],
                    [self::JURISDICTION_ID, '99', null, null, self::TIME, self::TIME],
                ],
            ],
            [
                ['100', '99', '98', '97', '96', '95', '94', '93', '92', '91'],
                [
                    [self::JURISDICTION_ID, null, '91', '100', self::TIME, self::TIME],
                ],
            ],
        ];
    }
}
