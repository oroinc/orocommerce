<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Basic\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

class BasicShippingContextBuilderFactory implements ShippingContextBuilderFactoryInterface
{
    /**
     * @var ShippingLineItemCollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @var ShippingOriginProvider
     */

    private $shippingOriginProvider;

    /**
     * @param ShippingLineItemCollectionFactoryInterface $collectionFactory
     * @param ShippingOriginProvider $shippingOriginProvider
     */
    public function __construct(
        ShippingLineItemCollectionFactoryInterface $collectionFactory,
        ShippingOriginProvider $shippingOriginProvider
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createShippingContextBuilder(
        $currency,
        Price $subTotal,
        $sourceEntity,
        $sourceEntityId
    ) {
        return new BasicShippingContextBuilder(
            $currency,
            $subTotal,
            $sourceEntity,
            $sourceEntityId,
            $this->collectionFactory,
            $this->shippingOriginProvider
        );
    }
}
