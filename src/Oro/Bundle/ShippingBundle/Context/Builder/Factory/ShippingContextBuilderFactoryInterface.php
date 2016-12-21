<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;

interface ShippingContextBuilderFactoryInterface
{
    /**
     * @param string           $currency
     * @param Price            $subTotal
     * @param object           $sourceEntity
     * @param string           $sourceEntityId
     *
     * @return ShippingContextBuilderInterface
     */
    public function createShippingContextBuilder(
        $currency,
        Price $subTotal,
        $sourceEntity,
        $sourceEntityId
    );
}
