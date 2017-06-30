<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Integration;

use Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayChannelType;

class PayPalPayflowGatewayChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayPalPayflowGatewayChannelType */
    private $channel;

    protected function setUp()
    {
        $this->channel = new PayPalPayflowGatewayChannelType();
    }

    public function testGetLabelReturnsString()
    {
        static::assertTrue(is_string($this->channel->getLabel()));
    }

    public function testGetIconReturnsString()
    {
        static::assertTrue(is_string($this->channel->getIcon()));
    }
}
