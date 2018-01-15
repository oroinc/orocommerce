<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexIntegrationSettingsType;
use Oro\Bundle\FedexShippingBundle\Integration\FedexTransport;
use PHPUnit\Framework\TestCase;

class FedexTransportTest extends TestCase
{
    public function testGetSettingsFormType()
    {
        static::assertSame(
            FedexIntegrationSettingsType::class,
            (new FedexTransport())->getSettingsFormType()
        );
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertSame(
            FedexIntegrationSettings::class,
            (new FedexTransport())->getSettingsEntityFQCN()
        );
    }

    public function testGetLabel()
    {
        static::assertSame(
            'oro.fedex.integration.settings.label',
            (new FedexTransport())->getLabel()
        );
    }
}
