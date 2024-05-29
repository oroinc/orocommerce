<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

/**
 * FedEx rate rest API response factory interface.
 */
interface FedexRateServiceResponseFactoryInterface
{
    public function create(?RestResponseInterface $response): FedexRateServiceResponseInterface;

    public function createExceptionResult(\Exception $exception): FedexRateServiceResponseInterface;
}
