<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Response\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use PHPUnit\Framework\TestCase;

class FedexRateServiceResponseTest extends TestCase
{
    public function testAccessors()
    {
        $severityCode = 'code';
        $severityMessage = 'message';
        $prices = ['1', '2'];

        $response = new FedexRateServiceResponse($severityCode, $severityMessage, $prices);

        static::assertSame($severityCode, $response->getSeverityCode());
        static::assertSame($severityMessage, $response->getSeverityMessage());
        static::assertSame($prices, $response->getPrices());
    }
}
