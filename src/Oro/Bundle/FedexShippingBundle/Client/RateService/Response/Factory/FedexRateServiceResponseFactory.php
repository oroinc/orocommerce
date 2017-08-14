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

        $severityCode = $soapResponse->HighestSeverity;

        $notifications = $soapResponse->Notifications;
        if (is_array($notifications)) {
            $notifications = $notifications[0];
        }
        $severityMessage = $notifications->Message;

        $prices = [];
        if ($this->isResponseHasPrices($severityCode)) {
            $prices = $this->getPrices($soapResponse);
        }
        
        return new FedexRateServiceResponse($severityCode, $severityMessage, $prices);
    }

    /**
     * @return FedexRateServiceResponse
     */
    private function createConnectionErrorResponse(): FedexRateServiceResponse
    {
        return new FedexRateServiceResponse(
            FedexRateServiceResponse::SEVERITY_ERROR,
            'Connection Error'
        );
    }

    /**
     * @param string $severityCode
     *
     * @return bool
     */
    private function isResponseHasPrices(string $severityCode): bool
    {
        return $severityCode !== FedexRateServiceResponse::SEVERITY_ERROR &&
            $severityCode !== FedexRateServiceResponse::SEVERITY_FAILURE;
    }

    /**
     * @param $soapResponse
     *
     * @return Price[]
     */
    private function getPrices($soapResponse): array
    {
        $prices = [];
        if (is_array($soapResponse->RateReplyDetails)) {
            foreach ($soapResponse->RateReplyDetails as $rateReply) {
                $serviceCode = $rateReply->ServiceType;
                $prices[$serviceCode] = $this->getPrice($rateReply);
            }
        } else {
            $rateReply = $soapResponse->RateReplyDetails;
            $prices[$rateReply->ServiceType] = $this->getPrice($rateReply);
        }

        return $prices;
    }

    /**
     * @param \StdClass $rateReply
     *
     * @return Price
     */
    private function getPrice(\StdClass $rateReply): Price
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
