<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;

/**
 * Defines the contract for creating FedEx requests from rate service settings.
 */
interface FedexRequestByRateServiceSettingsFactoryInterface
{
    /**
     * @param FedexRateServiceRequestSettingsInterface $settings
     *
     * @return FedexRequestInterface|null
     */
    public function create(FedexRateServiceRequestSettingsInterface $settings);
}
