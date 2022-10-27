<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductSettingsType;
use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductTransport;
use PHPUnit\Framework\TestCase;

class FixedProductTransportTest extends TestCase
{
    protected FixedProductTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new FixedProductTransport();
    }

    public function testInitCompiles(): void
    {
        $settings = new FixedProductSettings();

        $this->transport->init($settings);
    }

    public function testGetSettingsFormType(): void
    {
        $this->assertSame(FixedProductSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN(): void
    {
        $this->assertSame(FixedProductSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsString(): void
    {
        $this->assertTrue(is_string($this->transport->getLabel()));
    }
}
