<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;

interface FedexRateServiceClientInterface
{
    /**
     * @param FedexRequestInterface $request
     *
     * @return FedexRateServiceResponseInterface
     */
    public function send(FedexRequestInterface $request): FedexRateServiceResponseInterface;
}
