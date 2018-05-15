<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Integration;

use Oro\Bundle\FedexShippingBundle\Integration\FedexChannel;
use PHPUnit\Framework\TestCase;

class FedexChannelTest extends TestCase
{
    public function testGetLabel()
    {
        static::assertSame('oro.fedex.integration.channel.label', (new FedexChannel())->getLabel());
    }

    public function testGetIcon()
    {
        static::assertSame('bundles/orofedexshipping/img/fedex-logo.png', (new FedexChannel())->getIcon());
    }
}
