<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexRequestByIntegrationSettingsFactoryInterface
{
    public function create(FedexIntegrationSettings $settings): FedexRequestInterface;
}
