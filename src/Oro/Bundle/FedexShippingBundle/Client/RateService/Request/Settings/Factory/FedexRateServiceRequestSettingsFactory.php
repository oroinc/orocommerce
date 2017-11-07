<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettings;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class FedexRateServiceRequestSettingsFactory implements FedexRateServiceRequestSettingsFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(
        FedexIntegrationSettings $integrationSettings,
        ShippingContextInterface $shippingContext,
        ShippingServiceRule $rule
    ): FedexRateServiceRequestSettingsInterface {
        return new FedexRateServiceRequestSettings($integrationSettings, $shippingContext, $rule);
    }
}
