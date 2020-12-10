<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class FedexIntegrationSettingsTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new FedexIntegrationSettings(), [
            ['fedexTestMode', true],
            ['key', 'key'],
            ['password', 'password'],
            ['accountNumber', 'accountNumber'],
            ['meterNumber', 'meterNumber'],
            ['pickupType', 'pickupType'],
            ['unitOfWeight', 'unitOfWeight'],
            ['invalidateCacheAt', new \DateTime()],
            ['invalidateCacheAt', new \DateTime()],
            ['ignorePackageDimensions', true],
        ]);

        static::assertPropertyCollections(new FedexIntegrationSettings(), [
            ['shippingServices', new FedexShippingService()],
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBag()
    {
        static::assertEquals(new ParameterBag(), (new FedexIntegrationSettings())->getSettingsBag());
    }

    public function testGetDimensionsUnit()
    {
        $settings = new FedexIntegrationSettings();

        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);
        static::assertSame(FedexIntegrationSettings::DIMENSION_CM, $settings->getDimensionsUnit());

        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_LB);
        static::assertSame(FedexIntegrationSettings::DIMENSION_IN, $settings->getDimensionsUnit());
    }
}
