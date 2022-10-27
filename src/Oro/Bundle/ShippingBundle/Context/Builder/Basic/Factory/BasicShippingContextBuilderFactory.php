<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Basic\Factory;

use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

class BasicShippingContextBuilderFactory implements ShippingContextBuilderFactoryInterface
{
    /**
     * @var ShippingOriginProvider
     */

    private $shippingOriginProvider;

    public function __construct(ShippingOriginProvider $shippingOriginProvider)
    {
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createShippingContextBuilder($sourceEntity, $sourceEntityId)
    {
        return new BasicShippingContextBuilder(
            $sourceEntity,
            $sourceEntityId,
            $this->shippingOriginProvider
        );
    }
}
