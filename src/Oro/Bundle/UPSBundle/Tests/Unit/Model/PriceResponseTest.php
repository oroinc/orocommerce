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
        $this->priceResponse->parseJSON(
            '{
               "RateResponse":{
                  "RatedShipment":{
                     "Service": {
                        "Code":"02"
                     },
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
                '02' => Price::create('8.60', 'USD'),
            ],
            'ServiceOptionsCharges' => [
                '02' => Price::create('0.00', 'USD'),
            ],
            'TotalCharges'          => [
                '02' => Price::create('8.60', 'USD'),
            ]
        ];
        $this->assertEquals($expected, $this->priceResponse->getPricesByServices());
    }

    public function testParseResponsePriceAsArray()
    {
        $this->priceResponse->parseJSON(
            '{
               "RateResponse":{
                  "RatedShipment":[
                     {
                         "Service": {
                            "Code":"01"
                         },
                         "TransportationCharges":{
                            "CurrencyCode":"USD",
                            "MonetaryValue":"8.60"
                         }
                     },
                     {
                         "Service": {
                            "Code":"02"
                         },
                         "TransportationCharges":{
                            "CurrencyCode":"EUR",
                            "MonetaryValue":"3.40"
                         }
                     },
                     {
                         "Service": {
                            "Code":"03"
                         },
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
            '01' => Price::create('8.60', 'USD'),
            '02' => Price::create('3.40', 'EUR'),
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
        $this->priceResponse->parseJSON('');
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
