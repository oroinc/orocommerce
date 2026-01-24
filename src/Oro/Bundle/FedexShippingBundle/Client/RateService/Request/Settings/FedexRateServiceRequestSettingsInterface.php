<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Defines the contract for FedEx rate service request settings.
 *
 * Settings encapsulate the integration configuration, shipping context, and
 * service rules needed to construct a FedEx rate service request.
 */
interface FedexRateServiceRequestSettingsInterface
{
    public function getIntegrationSettings(): FedexIntegrationSettings;

    public function getShippingContext(): ShippingContextInterface;

    public function getShippingServiceRule(): ShippingServiceRule;
}
