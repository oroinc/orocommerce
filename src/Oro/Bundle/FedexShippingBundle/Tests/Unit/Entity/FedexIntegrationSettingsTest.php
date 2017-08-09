<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
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
            ['key', 'key'],
            ['password', 'password'],
            ['accountNumber', 'accountNumber'],
            ['meterNumber', 'meterNumber'],
            ['pickupType', 'pickupType'],
            ['unitOfWeight', 'unitOfWeight'],
        ]);

        static::assertPropertyCollections(new FedexIntegrationSettings(), [
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBag()
    {
        static::assertEquals(new ParameterBag(), (new FedexIntegrationSettings())->getSettingsBag());
    }
}
