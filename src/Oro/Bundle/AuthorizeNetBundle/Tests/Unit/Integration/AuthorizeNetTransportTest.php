<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Integration;

use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\AuthorizeNetBundle\Form\Type\AuthorizeNetSettingsType;
use Oro\Bundle\AuthorizeNetBundle\Integration\AuthorizeNetTransport;

class AuthorizeNetTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var AuthorizeNetTransport */
    private $transport;

    protected function setUp()
    {
        $this->transport = new AuthorizeNetTransport();
    }

    public function testInitCompiles()
    {
        $settings = new AuthorizeNetSettings();
        $this->transport->init($settings);
        $this->assertAttributeSame($settings->getSettingsBag(), 'settings', $this->transport);
    }

    public function testGetSettingsFormType()
    {
        $this->assertSame(AuthorizeNetSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        $this->assertSame(AuthorizeNetSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsString()
    {
        $this->assertTrue(is_string($this->transport->getLabel()));
    }
}
