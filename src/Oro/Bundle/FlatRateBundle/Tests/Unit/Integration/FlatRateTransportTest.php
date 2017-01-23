<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Integration;

use Oro\Bundle\FlatRateBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateBundle\Form\Type\FlatRateSettingsType;
use Oro\Bundle\FlatRateBundle\Integration\FlatRateTransport;

class FlatRateTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var FlatRateTransport */
    private $transport;

    protected function setUp()
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
