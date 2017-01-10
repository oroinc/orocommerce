<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

interface ShippingPriceProviderInterface
{
    /**
     * @param ShippingContextInterface $context
     *
     * @return ShippingMethodViewCollection
     */
    public function getApplicableMethodsViews(ShippingContextInterface $context);

    /**
     * @param ShippingContextInterface $context
     * @param string $methodId
     * @param string $typeId
     *
     * @return Price|null
     */
    public function getPrice(ShippingContextInterface $context, $methodId, $typeId);
}
