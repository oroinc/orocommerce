<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Integration;

use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType;

class PaymentTermChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentTermChannelType */
    private $channel;

    protected function setUp()
    {
        $this->channel = new PaymentTermChannelType();
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
