<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Response;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use PHPUnit\Framework\TestCase;

class FedexRateServiceResponseTest extends TestCase
{
    public function testAccessors(): void
    {
        $responseStatusCode = 450;
        $prices = ['1', '2'];
        $errors = ['test' => 'error'];

        $response = new FedexRateServiceResponse($responseStatusCode, $prices, $errors);

        static::assertSame($responseStatusCode, $response->getResponseStatusCode());
        static::assertSame($prices, $response->getPrices());
        static::assertSame($errors, $response->getErrors());
    }

    public function testIsSuccessful(): void
    {
        $response = new FedexRateServiceResponse(200, []);
        static::assertTrue($response->isSuccessful());

        $response = new FedexRateServiceResponse(400, []);
        static::assertFalse($response->isSuccessful());
    }
}
