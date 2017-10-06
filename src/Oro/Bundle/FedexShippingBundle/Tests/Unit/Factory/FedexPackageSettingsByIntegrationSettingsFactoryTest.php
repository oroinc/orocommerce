<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageSettingsByIntegrationSettingsFactory;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use PHPUnit\Framework\TestCase;

class FedexPackageSettingsByIntegrationSettingsFactoryTest extends TestCase
{
    public function testCreateWithKg()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        static::assertEquals(
            new FedexPackageSettings(
                FedexPackageSettings::MAX_PACKAGE_WEIGHT_KGS,
                FedexPackageSettings::MAX_PACKAGE_LENGTH_CM,
                FedexPackageSettings::MAX_PACKAGE_GIRTH_CM,
                FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
                FedexIntegrationSettings::DIMENSION_CM
            ),
            (new FedexPackageSettingsByIntegrationSettingsFactory())->create($settings)
        );
    }

    public function testCreateWithLb()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_LB);

        static::assertEquals(
            new FedexPackageSettings(
                FedexPackageSettings::MAX_PACKAGE_WEIGHT_LBS,
                FedexPackageSettings::MAX_PACKAGE_LENGTH_INCH,
                FedexPackageSettings::MAX_PACKAGE_GIRTH_INCH,
                FedexIntegrationSettings::UNIT_OF_WEIGHT_LB,
                FedexIntegrationSettings::DIMENSION_IN
            ),
            (new FedexPackageSettingsByIntegrationSettingsFactory())->create($settings)
        );
    }
}
