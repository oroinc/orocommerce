<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Response\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactory;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse;
use PHPUnit\Framework\TestCase;

class FedexRateServiceResponseFactoryTest extends TestCase
{
    /** @var FedexRateServiceResponseFactory */
    private $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new FedexRateServiceResponseFactory();
    }

    public function testCreateWithoutResponse(): void
    {
        self::assertEquals(500, $this->factory->create()->getResponseStatusCode());
    }

    public function testCreateOnEmptyResponseData(): void
    {
        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn([]);

        self::assertEquals(200, $this->factory->create($response)->getResponseStatusCode());
    }

    public function testCreateOnEmptyPriceResponseData(): void
    {
        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn(['output' => ['rateReplyDetails' => []]]);

        $responseObject = $this->factory->create($response);
        self::assertEquals(200, $responseObject->getResponseStatusCode());
        self::assertEquals([], $responseObject->getPrices());
    }

    public function testCreateSuccessMultipleRateReplies(): void
    {
        $services = ['service1', 'service2'];
        $prices = [
            Price::create(54.6, 'EUR'),
            Price::create(46.03, 'USD'),
        ];

        $restResponse = $this->createMock(RestResponseInterface::class);
        $restResponse->expects(self::once())
            ->method('json')
            ->willReturn([
                'output' => [
                    'rateReplyDetails' => [
                        $this->createRateReplyDetail($services[0], $prices[0]),
                        $this->createRateReplyDetail($services[1], $prices[1]),
                    ]
                ]
            ]);

        self::assertEquals(
            new FedexRateServiceResponse(
                200,
                [
                    $services[0] => $prices[0],
                    $services[1] => $prices[1],
                ]
            ),
            $this->factory->create($restResponse)
        );
    }

    public function testCreateExceptionResultOnCommonException(): void
    {
        $result = $this->factory->createExceptionResult(new \Exception());

        self::assertEquals(500, $result->getResponseStatusCode());
        self::assertEmpty($result->getErrors());
        self::assertEmpty($result->getPrices());
    }

    public function testCreateExceptionResultOnRestException(): void
    {
        $errors = [[
            'code' => 'NOT.FOUND.ERROR',
            'message' => 'We are unable to process this request. Please try again later.'
        ]];
        $response = new FakeRestResponse(401, [], \json_encode(['errors' => $errors]));
        $result = $this->factory->createExceptionResult(RestException::createFromResponse($response));

        self::assertEquals(401, $result->getResponseStatusCode());
        self::assertEquals($errors, $result->getErrors());
        self::assertEmpty($result->getPrices());
    }

    private function createRateReplyDetail(string $serviceName, Price $price): array
    {
        $rateReplyDetails = [];
        $rateReplyDetails['serviceType'] = $serviceName;
        $rateReplyDetails['ratedShipmentDetails'] = [];
        $rateReplyDetails['ratedShipmentDetails'][0]['totalNetCharge'] = $price->getValue();
        $rateReplyDetails['ratedShipmentDetails'][0]['shipmentRateDetail'] = [];
        $rateReplyDetails['ratedShipmentDetails'][0]['shipmentRateDetail']['currency'] = $price->getCurrency();

        return $rateReplyDetails;
    }
}
