<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory;

use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;

/**
 * The factory to create a basic payment context builder.
 */
class BasicPaymentContextBuilderFactory implements PaymentContextBuilderFactoryInterface
{
    private PaymentLineItemCollectionFactoryInterface $collectionFactory;

    public function __construct(PaymentLineItemCollectionFactoryInterface $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createPaymentContextBuilder(
        object $sourceEntity,
        mixed $sourceEntityId
    ): PaymentContextBuilderInterface {
        return new BasicPaymentContextBuilder(
            $sourceEntity,
            $sourceEntityId,
            $this->collectionFactory
        );
    }
}
