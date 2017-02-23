<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;

class FlatRateChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var FlatRateChannelType */
    private $channel;

    protected function setUp()
    {
        $this->channel = new FlatRateChannelType();
    }

    public function testGetLabelReturnsString()
    {
        static::assertTrue(is_string($this->channel->getLabel()));
    }
}
