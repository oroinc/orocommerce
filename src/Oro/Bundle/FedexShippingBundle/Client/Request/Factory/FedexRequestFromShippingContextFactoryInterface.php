<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface FedexRequestFromShippingContextFactoryInterface
{
    /**
     * @param FedexIntegrationSettings $settings
     * @param ShippingContextInterface $context
     *
     * @return FedexRequestInterface
     */
    public function create(
        FedexIntegrationSettings $settings,
        ShippingContextInterface $context
    ): FedexRequestInterface;
}
