<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Response\Factory;

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
}
