<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\Factory\BasicShippingContextBuilderFactory;

class BasicShippingContextBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateBuilder(): void
    {
        $sourceEntity = $this->createMock(Checkout::class);
        $sourceEntityId = '12';

        $factory = new BasicShippingContextBuilderFactory();

        self::assertEquals(
            new BasicShippingContextBuilder($sourceEntity, $sourceEntityId),
            $factory->createShippingContextBuilder($sourceEntity, $sourceEntityId)
        );
    }
}
