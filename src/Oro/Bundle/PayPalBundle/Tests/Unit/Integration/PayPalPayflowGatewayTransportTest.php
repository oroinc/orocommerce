<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Integration;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayTransport;

class PayPalPayflowGatewayTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayPalPayflowGatewayTransport */
    private $transport;

    protected function setUp()
    {
        $this->transport = new PayPalPayflowGatewayTransport();
    }

    public function testInitCompiles()
    {
         $settings = new PayPalSettings();
         $this->transport->init($settings);
         $this->assertAttributeSame($settings->getSettingsBag(), 'settings', $this->transport);
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
