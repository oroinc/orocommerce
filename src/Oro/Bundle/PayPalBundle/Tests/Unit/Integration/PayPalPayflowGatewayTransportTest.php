<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Integration;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayTransport;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalPayflowGatewayTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var PayPalPayflowGatewayTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->transport = new class() extends PayPalPayflowGatewayTransport {
            public function xgetSettings(): ParameterBag
            {
                return $this->settings;
            }
        };
    }

    public function testInitCompiles()
    {
        $settings = new PayPalSettings();
        $this->transport->init($settings);
        static::assertSame($settings->getSettingsBag(), $this->transport->xgetSettings());
    }

    public function testGetSettingsFormType()
    {
        static::assertSame(PayPalSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertSame(PayPalSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsString()
    {
        static::assertTrue(is_string($this->transport->getLabel()));
    }
}
