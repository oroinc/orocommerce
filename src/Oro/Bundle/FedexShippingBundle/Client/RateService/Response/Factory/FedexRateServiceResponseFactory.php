<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;

class FedexRateServiceResponseFactory implements FedexRateServiceResponseFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create($soapResponse): FedexRateServiceResponseInterface
    {
        if (!$soapResponse) {
            return $this->createConnectionErrorResponse();
        }

        $severityType = $soapResponse->HighestSeverity;

        $notifications = $soapResponse->Notifications;
        if (is_array($notifications)) {
            $notifications = $notifications[0];
        }
        $severityCode = $notifications->Code;

        $prices = [];
        if ($this->isResponseHasPrices($severityType, $soapResponse)) {
            $prices = $this->createPricesByResponse($soapResponse);
        }

        return new FedexRateServiceResponse($severityType, $severityCode, $prices);
    }

    private function createConnectionErrorResponse(): FedexRateServiceResponse
    {
        return new FedexRateServiceResponse(
            FedexRateServiceResponse::SEVERITY_ERROR,
            FedexRateServiceResponse::CONNECTION_ERROR
        );
    }

    /**
     * @param string $severityType
     * @param mixed  $soapResponse
     *
     * @return bool
     */
    private function isResponseHasPrices(string $severityType, $soapResponse): bool
    {
        return $severityType !== FedexRateServiceResponse::SEVERITY_ERROR &&
            $severityType !== FedexRateServiceResponse::SEVERITY_FAILURE &&
            property_exists($soapResponse, 'RateReplyDetails');
    }

    /**
     * @param $soapResponse
     *
     * @return Price[]
     */
    private function createPricesByResponse($soapResponse): array
    {
        $prices = [];
        if (is_array($soapResponse->RateReplyDetails)) {
            foreach ($soapResponse->RateReplyDetails as $rateReply) {
                $serviceCode = $rateReply->ServiceType;
                $prices[$serviceCode] = $this->createPriceByResponse($rateReply);
            }

            return $prices;
        }

        $rateReply = $soapResponse->RateReplyDetails;
        $prices[$rateReply->ServiceType] = $this->createPriceByResponse($rateReply);

        return $prices;
    }

    private function createPriceByResponse(\StdClass $rateReply): Price
    {
        if ($rateReply->RatedShipmentDetails && is_array($rateReply->RatedShipmentDetails)) {
            return Price::create(
                $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,
                $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Currency
            );
        }

        return Price::create(
            $rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount,
            $rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Currency
        );
    }
}
