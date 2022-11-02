<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

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

    /**
     * {@inheritDoc}
     */
    public function create(PaymentTransaction $transaction)
    {
        if ($this->compositeFactory->supports($transaction->getEntityClass(), $transaction->getEntityIdentifier())) {
            return $this->compositeFactory->create($transaction->getEntityClass(), $transaction->getEntityIdentifier());
        }

        return null;
    }
}
