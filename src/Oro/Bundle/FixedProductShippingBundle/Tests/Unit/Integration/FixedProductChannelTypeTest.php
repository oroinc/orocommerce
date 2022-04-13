<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductChannelType;
use PHPUnit\Framework\TestCase;

class FixedProductChannelTypeTest extends TestCase
{
    protected FixedProductChannelType $channel;

    protected function setUp(): void
    {
        $this->channel = new FixedProductChannelType();
    }

    public function testGetLabel(): void
    {
        $this->assertSame('oro.fixed_product.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon(): void
    {
        $this->assertSame(
            'bundles/orofixedproductshipping/img/fixed-product-logo.png',
            $this->channel->getIcon()
        );
    }
}
