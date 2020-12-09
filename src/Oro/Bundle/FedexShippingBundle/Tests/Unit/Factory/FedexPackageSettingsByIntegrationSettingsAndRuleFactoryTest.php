<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageSettingsByIntegrationSettingsAndRuleFactory;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use PHPUnit\Framework\TestCase;

class FedexPackageSettingsByIntegrationSettingsAndRuleFactoryTest extends TestCase
{
    public function testCreateWithKg()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);
        $settings->setIgnorePackageDimensions(true);

        $rule = new ShippingServiceRule();
        $rule->setLimitationExpressionKg('weight < 10');

        static::assertEquals(
            new FedexPackageSettings(
                FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
                FedexIntegrationSettings::DIMENSION_CM,
                'weight < 10',
                true
            ),
            (new FedexPackageSettingsByIntegrationSettingsAndRuleFactory())->create($settings, $rule)
        );
    }

    public function testCreateWithLb()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_LB);

        $rule = new ShippingServiceRule();
        $rule->setLimitationExpressionLbs('weight < 10');

        static::assertEquals(
            new FedexPackageSettings(
                FedexIntegrationSettings::UNIT_OF_WEIGHT_LB,
                FedexIntegrationSettings::DIMENSION_IN,
                'weight < 10'
            ),
            (new FedexPackageSettingsByIntegrationSettingsAndRuleFactory())->create($settings, $rule)
        );
    }
}
