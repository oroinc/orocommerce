<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\ShippingBundle\Integration\MultiShippingChannelType;
use PHPUnit\Framework\TestCase;

class MultiShippingChannelTypeTest extends TestCase
{
    public function testMultiShippingChannelType()
    {
        $channelType = new MultiShippingChannelType();

        $this->assertEquals('oro.shipping.multi_shipping_method.channel_type.label', $channelType->getLabel());
        $this->assertEquals('bundles/oroshipping/img/multi-shipping-logo.png', $channelType->getIcon());
    }
}
