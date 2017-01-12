<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Integration;

use Oro\Bundle\PayPalBundle\Integration\PayPalPaymentsProChannelType;

class PayPalPaymentsProChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayPalPaymentsProChannelType */
    private $channel;

    protected function setUp()
    {
        $this->channel = new PayPalPaymentsProChannelType();
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
