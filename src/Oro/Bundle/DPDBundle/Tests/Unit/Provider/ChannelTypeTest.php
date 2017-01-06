<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Oro\Bundle\DPDBundle\Provider\ChannelType;

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
        static::assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\ChannelInterface', $this->channel);
        static::assertEquals('oro.dpd.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        static::assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface', $this->channel);
        static::assertEquals('bundles/orodpd/img/DPD_logo_icon.png', $this->channel->getIcon());
    }
}
