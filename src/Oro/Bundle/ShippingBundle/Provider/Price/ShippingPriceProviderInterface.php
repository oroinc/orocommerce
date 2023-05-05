<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * Represents a service to provide views for all applicable shipping methods and calculate a shipping price
 * for a specific shipping context.
 */
interface ShippingPriceProviderInterface
{
    public function getApplicableMethodsViews(ShippingContextInterface $context): ShippingMethodViewCollection;

    public function getPrice(ShippingContextInterface $context, ?string $methodId, ?string $typeId): ?Price;
}
