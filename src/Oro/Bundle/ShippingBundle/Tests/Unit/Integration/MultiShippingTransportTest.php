<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Bundle\ShippingBundle\Form\Type\MultiShippingSettingsType;
use Oro\Bundle\ShippingBundle\Integration\MultiShippingTransport;
use PHPUnit\Framework\TestCase;

class MultiShippingTransportTest extends TestCase
{
    private MultiShippingTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new MultiShippingTransport();
    }

    public function testInit()
    {
        $this->transport->init(new MultiShippingSettings());
    }

    public function testGetSettingsFormType()
    {
        $this->assertSame(MultiShippingSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        $this->assertSame(MultiShippingSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabel()
    {
        $this->assertSame('oro.shipping.multi_shipping_method.settings.label', $this->transport->getLabel());
    }
}
