<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory;

use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;

/**
 * The factory to create a basic payment context builder.
 */
class BasicPaymentContextBuilderFactory implements PaymentContextBuilderFactoryInterface
{
    #[\Override]
    public function createPaymentContextBuilder(
        object $sourceEntity,
        mixed $sourceEntityId
    ): PaymentContextBuilderInterface {
        return new BasicPaymentContextBuilder($sourceEntity, $sourceEntityId);
    }
}
