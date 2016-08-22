<?php

namespace OroCRM\Bundle\MailChimpBundle\Tests\Unit\Provider;

use OroCRM\Bundle\MailChimpBundle\Provider\ChannelType;

class ChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChannelType */
    protected $channel;

    protected function setUp()
    {
        $this->channel = new ChannelType();
    }

    public function testGetLabel()
    {
        $this->assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\ChannelInterface', $this->channel);
        $this->assertEquals('orocrm.mailchimp.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        $this->assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface', $this->channel);
        $this->assertEquals('bundles/orocrmmailchimp/img/freddie.ico', $this->channel->getIcon());
    }
}
