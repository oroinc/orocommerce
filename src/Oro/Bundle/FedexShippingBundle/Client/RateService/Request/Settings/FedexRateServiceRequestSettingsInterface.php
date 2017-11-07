<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
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
     * @return ShippingService
     */
    public function getShippingService(): ShippingService;
}
