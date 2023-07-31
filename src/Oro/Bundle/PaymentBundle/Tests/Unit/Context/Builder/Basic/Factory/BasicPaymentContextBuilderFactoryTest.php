<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory\BasicPaymentContextBuilderFactory;
use PHPUnit\Framework\TestCase;

class BasicPaymentContextBuilderFactoryTest extends TestCase
{
    public function testCreateBuilder(): void
    {
        $sourceEntity = new \stdClass();
        $sourceEntityId = '12';

        $contextBuilderFactory = new BasicPaymentContextBuilderFactory();

        self::assertEquals(
            new BasicPaymentContextBuilder($sourceEntity, $sourceEntityId),
            $contextBuilderFactory->createPaymentContextBuilder($sourceEntity, $sourceEntityId)
        );
    }
}
