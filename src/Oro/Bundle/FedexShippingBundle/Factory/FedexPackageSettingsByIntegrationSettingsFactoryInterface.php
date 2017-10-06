<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;

interface FedexPackageSettingsByIntegrationSettingsFactoryInterface
{
    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return FedexPackageSettingsInterface
     */
    public function create(FedexIntegrationSettings $settings): FedexPackageSettingsInterface;
}
