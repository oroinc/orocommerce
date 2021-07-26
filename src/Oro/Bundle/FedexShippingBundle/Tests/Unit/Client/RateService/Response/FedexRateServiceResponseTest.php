<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Response;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use PHPUnit\Framework\TestCase;

class FedexRateServiceResponseTest extends TestCase
{
    public function testAccessors()
    {
        $severityType = 'code';
        $severityCode = 35;
        $prices = ['1', '2'];

        $response = new FedexRateServiceResponse($severityType, $severityCode, $prices);

        static::assertSame($severityType, $response->getSeverityType());
        static::assertSame($severityCode, $response->getSeverityCode());
        static::assertSame($prices, $response->getPrices());
    }

    public function testIsSuccessful()
    {
        $response = new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_SUCCESS, 0);
        static::assertTrue($response->isSuccessful());

        $response = new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_NOTE, 0);
        static::assertTrue($response->isSuccessful());

        $response = new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_WARNING, 0);
        static::assertFalse($response->isSuccessful());

        $response = new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_ERROR, 0);
        static::assertFalse($response->isSuccessful());

        $response = new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_FAILURE, 0);
        static::assertFalse($response->isSuccessful());
    }
}
