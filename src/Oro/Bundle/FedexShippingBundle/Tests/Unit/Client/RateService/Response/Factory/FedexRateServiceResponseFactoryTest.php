<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Response\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactory;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use PHPUnit\Framework\TestCase;

class FedexRateServiceResponseFactoryTest extends TestCase
{
    public function testCreateConnectionError()
    {
        static::assertEquals(
            $this->createErrorResponse(),
            (new FedexRateServiceResponseFactory())->create(null)
        );
    }

    public function testCreateErrorWithMultipleNotifications()
    {
        $message1 = $this->createNotificationMessage('1');
        $message2 = $this->createNotificationMessage('2');


        $soapResponse = new \StdClass();
        $soapResponse->HighestSeverity = FedexRateServiceResponse::SEVERITY_ERROR;
        $soapResponse->Notifications = [$message1, $message2];

        static::assertEquals(
            new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_ERROR, '1'),
            (new FedexRateServiceResponseFactory())->create($soapResponse)
        );
    }

    public function testCreateFailureWithOneNotification()
    {
        $message = $this->createNotificationMessage('1');

        $soapResponse = new \StdClass();
        $soapResponse->HighestSeverity = FedexRateServiceResponse::SEVERITY_FAILURE;
        $soapResponse->Notifications = $message;

        static::assertEquals(
            new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_FAILURE, '1'),
            (new FedexRateServiceResponseFactory())->create($soapResponse)
        );
    }

    public function testCreateWarningOneRateReply()
    {
        $price = 100.04;
        $currency = 'USD';
        $service = 'service1';
        $message = 'message';

        $rateReplyDetails = new \StdClass();
        $rateReplyDetails->ServiceType = $service;
        $rateReplyDetails->RatedShipmentDetails = new \StdClass();
        $rateReplyDetails->RatedShipmentDetails->ShipmentRateDetail = new \StdClass();
        $rateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge = new \StdClass();
        $rateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount = $price;
        $rateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Currency = $currency;

        $soapResponse = new \StdClass();
        $soapResponse->HighestSeverity = FedexRateServiceResponse::SEVERITY_WARNING;
        $soapResponse->Notifications = $this->createNotificationMessage($message);
        $soapResponse->RateReplyDetails = $rateReplyDetails;

        static::assertEquals(
            new FedexRateServiceResponse(
                FedexRateServiceResponse::SEVERITY_WARNING,
                $message,
                [$service => Price::create($price, $currency)]
            ),
            (new FedexRateServiceResponseFactory())->create($soapResponse)
        );
    }

    public function testCreateSuccessMultipleRateReplies()
    {
        $services = ['service1', 'service2'];
        $prices = [
            Price::create(54.6, 'EUR'),
            Price::create(46.03, 'USD'),
        ];
        $message = 'message';

        $soapResponse = new \StdClass();
        $soapResponse->HighestSeverity = FedexRateServiceResponse::SEVERITY_SUCCESS;
        $soapResponse->Notifications = $this->createNotificationMessage($message);
        $soapResponse->RateReplyDetails = [
            $this->createRateReplyDetail($services[0], $prices[0]),
            $this->createRateReplyDetail($services[1], $prices[1]),
        ];

        static::assertEquals(
            new FedexRateServiceResponse(
                FedexRateServiceResponse::SEVERITY_SUCCESS,
                $message,
                [
                    $services[0] => $prices[0],
                    $services[1] => $prices[1],
                ]
            ),
            (new FedexRateServiceResponseFactory())->create($soapResponse)
        );
    }

    /**
     * @param string $serviceName
     * @param Price  $price
     *
     * @return \StdClass
     */
    private function createRateReplyDetail(string $serviceName, Price $price): \StdClass
    {
        $rateReplyDetails = new \StdClass();
        $rateReplyDetails->ServiceType = $serviceName;
        $rateReplyDetails->RatedShipmentDetails = [new \StdClass()];
        $rateReplyDetails->RatedShipmentDetails[0]->ShipmentRateDetail = new \StdClass();
        $rateReplyDetails->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge = new \StdClass();
        $rateReplyDetails->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount = $price->getValue();
        $rateReplyDetails->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Currency =
            $price->getCurrency();

        return $rateReplyDetails;
    }

    /**
     * @param string $text
     *
     * @return \StdClass
     */
    private function createNotificationMessage(string $text): \StdClass
    {
        $message = new \StdClass();
        $message->Message = $text;

        return $message;
    }

    /**
     * @return FedexRateServiceResponse
     */
    private function createErrorResponse(): FedexRateServiceResponse
    {
        return new FedexRateServiceResponse(
            FedexRateServiceResponse::SEVERITY_ERROR,
            'Connection Error'
        );
    }
}
