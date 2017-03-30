<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Integration;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Integration\ApruveTransport;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;

class ApruveTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var ApruveTransport */
    private $transport;

    /**
     * {@inheritdoc}
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
        // todo@webevt: change to proper Apruve Settings form type, as soon as it is ready.
        static::assertSame(FormType::class, $this->transport->getSettingsFormType());
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
