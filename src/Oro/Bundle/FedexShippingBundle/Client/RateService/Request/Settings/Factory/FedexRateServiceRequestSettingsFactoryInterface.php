<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface FedexRateServiceRequestSettingsFactoryInterface
{
    /**
     * @param FedexIntegrationSettings $integrationSettings
     * @param ShippingContextInterface $shippingContext
     * @param ShippingService          $service
     *
     * @return FedexRateServiceRequestSettingsInterface
     */
    public function create(
        FedexIntegrationSettings $integrationSettings,
        ShippingContextInterface $shippingContext,
        ShippingService $service
    ): FedexRateServiceRequestSettingsInterface;
}
