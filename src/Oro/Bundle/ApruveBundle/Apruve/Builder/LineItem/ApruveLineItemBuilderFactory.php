<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem;

class ApruveLineItemBuilderFactory implements ApruveLineItemBuilderFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create($title, $amountCents, $quantity, $currency)
    {
        return new ApruveLineItemBuilder($title, $amountCents, $quantity, $currency);
    }
}
