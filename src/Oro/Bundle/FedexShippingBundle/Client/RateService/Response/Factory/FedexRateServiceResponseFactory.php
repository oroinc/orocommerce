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

        $price = null;
        if ($this->isResponseHasPrices($severityType, $soapResponse)) {
            $price = $this->createPricesByResponse($soapResponse)[0];
        }
        
        return new FedexRateServiceResponse($severityType, $severityCode, $price);
    }

    /**
     * @return FedexRateServiceResponse
     */
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
                $prices[] = $this->createPriceByResponse($rateReply);
            }

            return $prices;
        }

        $rateReply = $soapResponse->RateReplyDetails;
        $prices[] = $this->createPriceByResponse($rateReply);

        return $prices;
    }

    /**
     * @param \StdClass $rateReply
     *
     * @return Price
     */
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
