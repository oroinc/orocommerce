<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexRateServiceBySettingsClientInterface
{
    /**
     * @param FedexRequestInterface    $request
     * @param FedexIntegrationSettings $settings
     *
     * @return FedexRateServiceResponseInterface
     */
    public function send(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexRateServiceResponseInterface;
}
