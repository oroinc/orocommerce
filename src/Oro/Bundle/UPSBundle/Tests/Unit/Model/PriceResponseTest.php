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
        $this->priceResponse->parse(json_decode(
            '{
                "RateResponse":{
                    "RatedShipment":{
                        "Service": {
                            "Code":"02"
                        },
                        "TotalCharges":{
                            "CurrencyCode":"USD",
                            "MonetaryValue":"8.60"
                        }
                    }
                }
            }',
            true
        ));
        $expected = [
            '02' => Price::create('8.60', 'USD'),
        ];
        static::assertEquals($expected, $this->priceResponse->getPricesByServices());
    }

    public function testParseResponsePriceAsArray()
    {
        $this->priceResponse->parse(json_decode(
            '{
                "RateResponse":{
                    "RatedShipment":{
                        "Service": {
                            "Code":"01"
                        },
                        "TotalCharges":{
                            "CurrencyCode":"USD",
                            "MonetaryValue":"8.60"
                        }
                    }
                }
            }',
            true
        ));

        $pricesExpected = [
            '01' => Price::create('8.60', 'USD'),
        ];

        static::assertEquals($pricesExpected, $this->priceResponse->getPricesByServices());
        static::assertEquals($pricesExpected['01'], $this->priceResponse->getPriceByService('01'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No price data in provided string
     */
    public function testParseEmptyResponse()
    {
        $this->priceResponse->parse(json_decode('', true));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Response data not loaded
     */
    public function testGetPriceByServicesException()
    {
        $this->priceResponse->getPricesByServices();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Price data is missing for service fakeService
     */
    public function testGetPriceByServiceException()
    {
        $this->priceResponse->getPriceByService('fakeService');
    }
}
