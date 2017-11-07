<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface FedexRateServiceRequestSettingsInterface
{
    /**
     * @return FedexIntegrationSettings
     */
    public function getIntegrationSettings(): FedexIntegrationSettings;

    /**
     * @return ShippingContextInterface
     */
    public function getShippingContext(): ShippingContextInterface;

    /**
     * @return ShippingServiceRule
     */
    public function getShippingServiceRule(): ShippingServiceRule;
}
