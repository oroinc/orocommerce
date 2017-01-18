<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Integration;

use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType;

class MoneyOrderChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var MoneyOrderChannelType */
    private $channel;

    protected function setUp()
    {
        $this->channel = new MoneyOrderChannelType();
    }

    public function testGetLabelReturnsCorrectString()
    {
        static::assertSame('oro.money_order.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIconReturnsCorrectString()
    {
        static::assertSame(
            'bundles/oromoneyorder/img/money-order-icon.png',
            $this->channel->getIcon()
        );
    }
}
