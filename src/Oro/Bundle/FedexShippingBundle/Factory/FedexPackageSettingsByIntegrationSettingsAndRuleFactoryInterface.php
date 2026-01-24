<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;

/**
 * Defines the contract for creating FedEx package settings from integration settings and rules.
 */
interface FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface
{
    public function create(
        FedexIntegrationSettings $settings,
        ShippingServiceRule $rule
    ): FedexPackageSettingsInterface;
}
