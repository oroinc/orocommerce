<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;

interface FedexPackageSettingsByIntegrationSettingsAndShippingServiceFactoryInterface
{
    /**
     * @param FedexIntegrationSettings $settings
     * @param ShippingService          $service
     *
     * @return FedexPackageSettingsInterface
     */
    public function create(FedexIntegrationSettings $settings, ShippingService $service): FedexPackageSettingsInterface;
}
