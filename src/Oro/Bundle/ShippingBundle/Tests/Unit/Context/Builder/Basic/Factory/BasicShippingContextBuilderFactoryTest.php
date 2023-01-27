<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\Factory\BasicShippingContextBuilderFactory;

class BasicShippingContextBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateBuilder(): void
    {
        $sourceEntity = $this->createMock(\stdClass::class);
        $sourceEntityId = '12';

        $contextBuilderFactory = new BasicShippingContextBuilderFactory();

        self::assertEquals(
            new BasicShippingContextBuilder($sourceEntity, $sourceEntityId),
            $contextBuilderFactory->createShippingContextBuilder($sourceEntity, $sourceEntityId)
        );
    }
}
