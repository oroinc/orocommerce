<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;

interface FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface
{
    public function create(
        FedexIntegrationSettings $settings,
        ShippingServiceRule $rule
    ): FedexPackageSettingsInterface;
}
