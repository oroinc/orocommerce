<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory;

use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

interface LineItemBuilderByLineItemFactoryInterface
{
    public function createBuilder(ShippingLineItemInterface $lineItem): ShippingLineItemBuilderInterface;
}
