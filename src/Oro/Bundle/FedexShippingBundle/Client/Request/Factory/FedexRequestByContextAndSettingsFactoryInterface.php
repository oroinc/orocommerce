<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexRequestByContextAndSettingsFactoryInterface
{
    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return FedexRequestInterface|null
     */
    public function create(FedexIntegrationSettings $settings, ShippingContextInterface $context);
}
