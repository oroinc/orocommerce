<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateSettingsType;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateTransport;

class FlatRateTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var FlatRateTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->transport = new FlatRateTransport();
    }

    public function testInitCompiles()
    {
        $settings = new FlatRateSettings();

        $this->transport->init($settings);
    }

    public function testGetSettingsFormType()
    {
        static::assertSame(FlatRateSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertSame(FlatRateSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsString()
    {
        static::assertTrue(is_string($this->transport->getLabel()));
    }
}
