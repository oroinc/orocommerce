<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;

interface FedexRateServiceResponseFactoryInterface
{
    /**
     * @param mixed $soapResponse
     *
     * @return FedexRateServiceResponseInterface
     */
    public function create($soapResponse): FedexRateServiceResponseInterface;
}
