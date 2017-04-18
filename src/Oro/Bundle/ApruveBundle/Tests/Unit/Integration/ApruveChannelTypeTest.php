<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Integration;

use Oro\Bundle\ApruveBundle\Integration\ApruveChannelType;

class ApruveChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveChannelType
     */
    private $channel;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->channel = new ApruveChannelType();
    }

    public function testGetLabelReturnsCorrectString()
    {
        static::assertSame('oro.apruve.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        static::assertSame('bundles/oroapruve/img/apruve-logo.png', $this->channel->getIcon());
    }
}
