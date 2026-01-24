<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Creates payment context from a payment transaction.
 *
 * This factory delegates to a composite factory to create a payment context based on the
 * entity class and identifier stored in the payment transaction. Returns null if the entity
 * type is not supported by any registered factory.
 */
class TransactionPaymentContextFactory implements TransactionPaymentContextFactoryInterface
{
    /**
     * @var CompositeSupportsEntityPaymentContextFactory
     */
    private $compositeFactory;

    public function __construct(CompositeSupportsEntityPaymentContextFactory $compositeFactory)
    {
        $this->compositeFactory = $compositeFactory;
    }

    #[\Override]
    public function create(PaymentTransaction $transaction)
    {
        if ($this->compositeFactory->supports($transaction->getEntityClass(), $transaction->getEntityIdentifier())) {
            return $this->compositeFactory->create($transaction->getEntityClass(), $transaction->getEntityIdentifier());
        }

        return null;
    }
}
