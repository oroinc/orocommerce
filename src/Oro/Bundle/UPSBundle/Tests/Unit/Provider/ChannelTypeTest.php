<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Provider;

use Oro\Bundle\UPSBundle\Provider\ChannelType;

class ChannelTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChannelType */
    protected $channel;

    protected function setUp(): void
    {
        $this->channel = new ChannelType();
    }

    public function testGetLabel()
    {
        static::assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\ChannelInterface', $this->channel);
        static::assertEquals('oro.ups.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        static::assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface', $this->channel);
        static::assertEquals('bundles/oroups/img/ups-logo.gif', $this->channel->getIcon());
    }
}
