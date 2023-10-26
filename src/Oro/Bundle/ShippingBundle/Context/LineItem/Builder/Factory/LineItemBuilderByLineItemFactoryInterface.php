<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory;

use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

/**
 * Interface for the factory builder of a shipping line item model.
 *
 * @deprecated since 5.1
 */
interface LineItemBuilderByLineItemFactoryInterface
{
    public function createBuilder(ShippingLineItemInterface $lineItem): ShippingLineItemBuilderInterface;
}
