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

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(new FedexIntegrationSettings(), [
            ['fedexTestMode', true],
            ['key', 'key'],
            ['password', 'password'],
            ['clientId', 'clientId'],
            ['clientSecret', 'clientSecret'],
            ['accountNumber', 'accountNumber'],
            ['meterNumber', 'meterNumber'],
            ['accessToken', 'accessToken'],
            ['accessTokenExpiresAt', new \DateTime()],
            ['pickupType', 'pickupType'],
            ['pickupTypeSoap', 'pickupTypeSoap'],
            ['unitOfWeight', 'unitOfWeight'],
            ['invalidateCacheAt', new \DateTime()],
            ['invalidateCacheAt', new \DateTime()],
            ['ignorePackageDimensions', true],
        ]);

        self::assertPropertyCollections(new FedexIntegrationSettings(), [
            ['shippingServices', new FedexShippingService()],
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBag(): void
    {
        self::assertEquals(new ParameterBag(), (new FedexIntegrationSettings())->getSettingsBag());
    }

    public function testGetDimensionsUnit(): void
    {
        $settings = new FedexIntegrationSettings();

        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);
        self::assertSame(FedexIntegrationSettings::DIMENSION_CM, $settings->getDimensionsUnit());

        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_LB);
        self::assertSame(FedexIntegrationSettings::DIMENSION_IN, $settings->getDimensionsUnit());
    }
}
