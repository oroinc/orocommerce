<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Integration;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Form\Type\ApruveSettingsType;
use Oro\Bundle\ApruveBundle\Integration\ApruveTransport;

class ApruveTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveTransport
     */
    private $transport;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->transport = new ApruveTransport();
    }

    public function testInitCompiles()
    {
        $settings = new ApruveSettings();
        $this->transport->init($settings);
        $this->assertAttributeSame($settings->getSettingsBag(), 'settings', $this->transport);
    }

    public function testGetSettingsFormType()
    {
        static::assertSame(ApruveSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertSame(ApruveSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsCorrectString()
    {
        static::assertSame('oro.apruve.settings.label', $this->transport->getLabel());
    }
}
