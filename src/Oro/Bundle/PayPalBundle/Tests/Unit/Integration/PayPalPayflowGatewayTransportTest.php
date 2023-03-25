<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Integration;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayTransport;
use Oro\Component\Testing\ReflectionUtil;

class PayPalPayflowGatewayTransportTest extends \PHPUnit\Framework\TestCase
{
    private PayPalPayflowGatewayTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new PayPalPayflowGatewayTransport();
    }

    public function testInitCompiles()
    {
        $settings = new PayPalSettings();

        $this->transport->init($settings);

        self::assertSame(
            $settings->getSettingsBag(),
            ReflectionUtil::getPropertyValue($this->transport, 'settings')
        );
    }

    public function testGetSettingsFormType()
    {
        self::assertSame(PayPalSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        self::assertSame(PayPalSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsString()
    {
        self::assertIsString($this->transport->getLabel());
    }
}
