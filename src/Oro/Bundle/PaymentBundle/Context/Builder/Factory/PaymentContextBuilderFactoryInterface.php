<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Factory;

use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;

/**
 * Represents a factory to create a payment context builder.
 */
interface PaymentContextBuilderFactoryInterface
{
    public function createPaymentContextBuilder(
        object $sourceEntity,
        mixed $sourceEntityId
    ): PaymentContextBuilderInterface;
}
