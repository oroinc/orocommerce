<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Model\PriceResponse;

class PriceResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PriceResponse
     */
    protected $priceResponse;

    protected function setUp(): void
    {
        $this->priceResponse = new PriceResponse();
    }

    public function testParseResponseSinglePrice()
    {
        $this->priceResponse->parse(
            [
                'RateResponse' => [
                    'RatedShipment' => [
                        'Service' => [
                            'Code' => '02'
                        ],
                        'TotalCharges' => [
                            'CurrencyCode' => 'USD',
                            'MonetaryValue' => '8.60'
                        ]
                    ]
                ]
            ]
        );
        $expected = [
            '02' => Price::create('8.60', 'USD'),
        ];
        static::assertEquals($expected, $this->priceResponse->getPricesByServices());
    }

    public function testParseResponseMultiplePrices()
    {
        $this->priceResponse->parse(
            [
                'RateResponse' => [
                    'RatedShipment' => [
                        [
                            'Service' => [
                                'Code' => '02'
                            ],
                            'TotalCharges' => [
                                'CurrencyCode' => 'USD',
                                'MonetaryValue' => '8.60'
                            ]
                        ],
                        [
                            'Service' => [
                                'Code' => '12'
                            ],
                            'TotalCharges' => [
                                'CurrencyCode' => 'USD',
                                'MonetaryValue' => '18.60'
                            ]
                        ],
                    ]
                ]
            ]
        );

        $pricesExpected = [
            '02' => Price::create('8.60', 'USD'),
            '12' => Price::create('18.60', 'USD'),
        ];

        static::assertEquals($pricesExpected, $this->priceResponse->getPricesByServices());
        static::assertEquals($pricesExpected['02'], $this->priceResponse->getPriceByService('02'));
        static::assertEquals($pricesExpected['12'], $this->priceResponse->getPriceByService('12'));
    }

    public function testParseEmptyResponse()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No price data in provided string');

        $this->priceResponse->parse([]);
    }

    public function testGetPriceByServicesException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Response data not loaded');

        $this->priceResponse->getPricesByServices();
    }

    public function testGetPriceByService()
    {
        static::assertNull($this->priceResponse->getPriceByService('fakeService'));
    }
}
