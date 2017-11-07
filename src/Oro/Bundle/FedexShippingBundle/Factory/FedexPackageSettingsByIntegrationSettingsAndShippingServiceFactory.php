<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;

class FedexPackageSettingsByIntegrationSettingsAndShippingServiceFactory implements
    FedexPackageSettingsByIntegrationSettingsAndShippingServiceFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(FedexIntegrationSettings $settings, ShippingService $service): FedexPackageSettingsInterface
    {
        return new FedexPackageSettings(
            $settings->getUnitOfWeight(),
            $settings->getDimensionsUnit(),
            $this->getLimitationExpression($settings, $service)
        );
    }

    /**
     * @param FedexIntegrationSettings $settings
     * @param ShippingService          $service
     *
     * @return string
     */
    private function getLimitationExpression(FedexIntegrationSettings $settings, ShippingService $service): string
    {
        if ($settings->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return $service->getLimitationExpressionLbs();
        }

        return $service->getLimitationExpressionKg();
    }
}
