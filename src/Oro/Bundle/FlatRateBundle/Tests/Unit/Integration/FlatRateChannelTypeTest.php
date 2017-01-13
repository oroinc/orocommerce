<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Integration;

use Oro\Bundle\FlatRateBundle\Integration\FlatRateChannelType;

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

    public function testGetIconReturnsString()
    {
        static::assertTrue(is_string($this->channel->getIcon()));
    }
}
