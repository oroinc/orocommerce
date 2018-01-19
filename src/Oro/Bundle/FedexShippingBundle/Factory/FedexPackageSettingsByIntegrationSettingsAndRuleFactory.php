<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;

class FedexPackageSettingsByIntegrationSettingsAndRuleFactory implements
    FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(FedexIntegrationSettings $settings, ShippingServiceRule $rule): FedexPackageSettingsInterface
    {
        return new FedexPackageSettings(
            $settings->getUnitOfWeight(),
            $settings->getDimensionsUnit(),
            $this->getLimitationExpression($settings, $rule)
        );
    }

    /**
     * @param FedexIntegrationSettings $settings
     * @param ShippingServiceRule      $rule
     *
     * @return string
     */
    private function getLimitationExpression(FedexIntegrationSettings $settings, ShippingServiceRule $rule): string
    {
        if ($settings->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return $rule->getLimitationExpressionLbs();
        }

        return $rule->getLimitationExpressionKg();
    }
}
