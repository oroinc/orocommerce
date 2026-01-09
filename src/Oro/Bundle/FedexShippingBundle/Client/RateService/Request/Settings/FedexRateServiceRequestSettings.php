<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Encapsulates FedEx rate service request settings.
 *
 * This class combines integration settings, shipping context, and service rules
 * into a single object that can be used to construct FedEx rate service requests.
 */
class FedexRateServiceRequestSettings implements FedexRateServiceRequestSettingsInterface
{
    /**
     * @var FedexIntegrationSettings
     */
    private $integrationSettings;

    /**
     * @var ShippingContextInterface
     */
    private $shippingContext;

    /**
     * @var ShippingServiceRule
     */
    private $rule;

    public function __construct(
        FedexIntegrationSettings $integrationSettings,
        ShippingContextInterface $shippingContext,
        ShippingServiceRule $rule
    ) {
        $this->integrationSettings = $integrationSettings;
        $this->shippingContext = $shippingContext;
        $this->rule = $rule;
    }

    #[\Override]
    public function getIntegrationSettings(): FedexIntegrationSettings
    {
        return $this->integrationSettings;
    }

    #[\Override]
    public function getShippingContext(): ShippingContextInterface
    {
        return $this->shippingContext;
    }

    #[\Override]
    public function getShippingServiceRule(): ShippingServiceRule
    {
        return $this->rule;
    }
}
