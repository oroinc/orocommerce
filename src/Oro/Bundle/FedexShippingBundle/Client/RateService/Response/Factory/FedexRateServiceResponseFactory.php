<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

/**
 * FedEx rate rest API response factory.
 */
class FedexRateServiceResponseFactory implements FedexRateServiceResponseFactoryInterface
{
    #[\Override]
    public function createExceptionResult(\Exception $exception): FedexRateServiceResponseInterface
    {
        $responseStatus = 500;
        $errors = [];

        if ($exception instanceof RestException) {
            $responseStatus = $exception->getCode();
            $response = $exception->getResponse();
            if ($response) {
                try {
                    $errors = $response->json()['errors'];
                } catch (\Exception $e) {
                }
            }
        }

        return new FedexRateServiceResponse($responseStatus, [], $errors);
    }

    #[\Override]
    public function create(?RestResponseInterface $response = null): FedexRateServiceResponseInterface
    {
        if (!$response) {
            return new FedexRateServiceResponse(500);
        }

        /** @var array $data */
        $data = $response->json();
        if (!\is_array($data)) {
            return new FedexRateServiceResponse(400);
        }

        $prices = [];
        if (
            \array_key_exists('output', $data)
            && \array_key_exists('rateReplyDetails', $data['output'])
        ) {
            $prices = $this->createPricesByResponse($data['output']['rateReplyDetails']);
        }

        return new FedexRateServiceResponse(200, $prices);
    }

    /**
     * @return Price[]
     */
    private function createPricesByResponse(array $rateReplyDetails): array
    {
        $prices = [];
        foreach ($rateReplyDetails as $rateReply) {
            $serviceCode = $rateReply['serviceType'];
            $prices[$serviceCode] = $this->createPriceByResponse($rateReply);
        }

        return $prices;
    }

    private function createPriceByResponse(array $rateReply): Price
    {
        if ($rateReply['ratedShipmentDetails'] && \array_key_exists(0, $rateReply['ratedShipmentDetails'])) {
            return Price::create(
                $rateReply['ratedShipmentDetails'][0]['totalNetCharge'],
                $rateReply['ratedShipmentDetails'][0]['shipmentRateDetail']['currency'],
            );
        }

        return Price::create(
            $rateReply['ratedShipmentDetails']['totalNetCharge'],
            $rateReply['ratedShipmentDetails']['shipmentRateDetail']['currency'],
        );
    }
}
