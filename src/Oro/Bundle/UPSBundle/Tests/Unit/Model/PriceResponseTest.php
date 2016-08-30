<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Model\PriceResponse;

class PriceResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceResponse
     */
    protected $priceResponse;

    public function setUp()
    {
        $this->priceResponse = new PriceResponse();
    }

    public function testParseResponsePriceAsObject()
    {
        $this->priceResponse->setJSON(
            '{
               "RateResponse":{
                  "RatedShipment":{
                     "TransportationCharges":{
                        "CurrencyCode":"USD",
                        "MonetaryValue":"8.60"
                     },
                     "ServiceOptionsCharges":{
                        "CurrencyCode":"USD",
                        "MonetaryValue":"0.00"
                     },
                     "TotalCharges":{
                        "CurrencyCode":"USD",
                        "MonetaryValue":"8.60"
                     }
                  }
               }
            }'
        );
        $expected = [
            'TransportationCharges' => [
                Price::create('8.60', 'USD'),
            ],
            'ServiceOptionsCharges' => [
                Price::create('0.00', 'USD'),
            ],
            'TotalCharges'          => [
                Price::create('8.60', 'USD'),
            ]
        ];
        $this->assertEquals($expected, $this->priceResponse->getPricesByServices());
    }

    public function testParseResponsePriceAsArray()
    {
        $this->priceResponse->setJSON(
            '{
               "RateResponse":{
                  "RatedShipment":[
                     {
                         "TransportationCharges":{
                            "CurrencyCode":"USD",
                            "MonetaryValue":"8.60"
                         }
                     },
                     {
                         "TransportationCharges":{
                            "CurrencyCode":"EUR",
                            "MonetaryValue":"3.40"
                         }
                     },
                     {
                         "TransportationCharges":{
                            "CurrencyCode":"EUR",
                            "WrongMonetaryValue":"3.40"
                         }
                     }
                  ]
               }
            }'
        );

        $pricesExpected = [
            Price::create('8.60', 'USD'),
            Price::create('3.40', 'EUR'),
        ];
        $expected = [
            'TransportationCharges' => $pricesExpected
        ];
        $this->assertEquals($expected, $this->priceResponse->getPricesByServices());
        $this->assertEquals(
            $pricesExpected,
            $this->priceResponse->getPricesByService(PriceResponse::TRANSPORTATION_CHARGES)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No price data in provided string
     */
    public function testParseEmptyResponse()
    {
        $this->priceResponse->setJSON('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Response data not loaded
     */
    public function testGetPricesByServicesException()
    {
        $this->priceResponse->getPricesByServices();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Price data is missing for service fakeService
     */
    public function testGetPricesByService()
    {
        $this->priceResponse->getPricesByService('fakeService');
    }
}
