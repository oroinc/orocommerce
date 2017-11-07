<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexRequestByIntegrationSettingsFactoryInterface
{
    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return FedexRequestInterface
     */
    public function create(FedexIntegrationSettings $settings): FedexRequestInterface;
}
