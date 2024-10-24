<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

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
