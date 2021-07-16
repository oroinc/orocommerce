<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface FedexRateServiceRequestSettingsInterface
{
    public function getIntegrationSettings(): FedexIntegrationSettings;

    public function getShippingContext(): ShippingContextInterface;

    public function getShippingServiceRule(): ShippingServiceRule;
}
