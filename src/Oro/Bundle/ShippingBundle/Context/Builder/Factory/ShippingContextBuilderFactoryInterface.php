<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Factory;

use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;

interface ShippingContextBuilderFactoryInterface
{
    /**
     * @param object           $sourceEntity
     * @param string           $sourceEntityId
     *
     * @return ShippingContextBuilderInterface
     */
    public function createShippingContextBuilder($sourceEntity, $sourceEntityId);
}
