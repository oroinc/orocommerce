<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;

class FlatRateChannelTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var FlatRateChannelType */
    private $channel;

    protected function setUp(): void
    {
        $this->channel = new FlatRateChannelType();
    }

    public function testGetLabel()
    {
        static::assertSame('oro.flat_rate.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        static::assertSame(
            'bundles/oroflatrateshipping/img/flat-rate-logo.png',
            $this->channel->getIcon()
        );
    }
}
