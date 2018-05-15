<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;

interface FedexRequestByRateServiceSettingsFactoryInterface
{
    /**
     * @param FedexRateServiceRequestSettingsInterface $settings
     *
     * @return FedexRequestInterface|null
     */
    public function create(FedexRateServiceRequestSettingsInterface $settings);
}
