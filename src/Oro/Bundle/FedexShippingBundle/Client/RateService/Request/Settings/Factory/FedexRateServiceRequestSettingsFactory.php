<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettings;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Creates FedEx rate service request settings from integration settings, shipping context, and rules.
 *
 * This factory combines integration configuration, shipping context information, and
 * service rules into FedexRateServiceRequestSettings objects used for rate requests.
 */
class FedexRateServiceRequestSettingsFactory implements FedexRateServiceRequestSettingsFactoryInterface
{
    #[\Override]
    public function create(
        FedexIntegrationSettings $integrationSettings,
        ShippingContextInterface $shippingContext,
        ShippingServiceRule $rule
    ): FedexRateServiceRequestSettingsInterface {
        return new FedexRateServiceRequestSettings($integrationSettings, $shippingContext, $rule);
    }
}
