<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;

class BasicPaymentContextBuilderFactory implements PaymentContextBuilderFactoryInterface
{
    /**
     * @var PaymentLineItemCollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @param PaymentLineItemCollectionFactoryInterface $collectionFactory
     */
    public function __construct(
        PaymentLineItemCollectionFactoryInterface $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createPaymentContextBuilder(
        $currency,
        Price $subTotal,
        $sourceEntity,
        $sourceEntityId
    ) {
        return new BasicPaymentContextBuilder(
            $currency,
            $subTotal,
            $sourceEntity,
            $sourceEntityId,
            $this->collectionFactory
        );
    }
}
