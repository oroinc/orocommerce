<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem;

interface ApruveLineItemBuilderFactoryInterface
{
    /**
     * @param string $title
     * @param int    $amountCents
     * @param int    $quantity
     * @param string $currency
     *
     * @return ApruveLineItemBuilderInterface
     */
    public function create($title, $amountCents, $quantity, $currency);
}
