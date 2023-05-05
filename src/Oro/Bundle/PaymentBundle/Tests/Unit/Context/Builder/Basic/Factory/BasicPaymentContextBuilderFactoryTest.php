<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory\BasicPaymentContextBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;

class BasicPaymentContextBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateBuilder()
    {
        $sourceEntity = $this->createMock(\stdClass::class);
        $sourceEntityId = '12';

        $lineItemCollectionFactory = $this->createMock(PaymentLineItemCollectionFactoryInterface::class);

        $contextBuilderFactory = new BasicPaymentContextBuilderFactory($lineItemCollectionFactory);

        self::assertEquals(
            new BasicPaymentContextBuilder($sourceEntity, $sourceEntityId, $lineItemCollectionFactory),
            $contextBuilderFactory->createPaymentContextBuilder($sourceEntity, $sourceEntityId)
        );
    }
}
