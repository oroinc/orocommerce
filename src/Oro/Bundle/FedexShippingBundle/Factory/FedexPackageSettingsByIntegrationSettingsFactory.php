<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;

class FedexPackageSettingsByIntegrationSettingsFactory implements
    FedexPackageSettingsByIntegrationSettingsFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(FedexIntegrationSettings $settings): FedexPackageSettingsInterface
    {
        return new FedexPackageSettings(
            $this->getMaxWeightValue($settings),
            $this->getMaxLengthValue($settings),
            $this->getMaxGirthValue($settings),
            $settings->getUnitOfWeight(),
            $settings->getDimensionsUnit()
        );
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return float
     */
    private function getMaxWeightValue(FedexIntegrationSettings $settings): float
    {
        if ($settings->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return FedexPackageSettings::MAX_PACKAGE_WEIGHT_LBS;
        }

        return FedexPackageSettings::MAX_PACKAGE_WEIGHT_KGS;
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return float
     */
    private function getMaxLengthValue(FedexIntegrationSettings $settings): float
    {
        if ($settings->getDimensionsUnit() === FedexIntegrationSettings::DIMENSION_CM) {
            return FedexPackageSettings::MAX_PACKAGE_LENGTH_CM;
        }

        return FedexPackageSettings::MAX_PACKAGE_LENGTH_INCH;
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return float
     */
    private function getMaxGirthValue(FedexIntegrationSettings $settings): float
    {
        if ($settings->getDimensionsUnit() === FedexIntegrationSettings::DIMENSION_CM) {
            return FedexPackageSettings::MAX_PACKAGE_GIRTH_CM;
        }

        return FedexPackageSettings::MAX_PACKAGE_GIRTH_INCH;
    }
}
