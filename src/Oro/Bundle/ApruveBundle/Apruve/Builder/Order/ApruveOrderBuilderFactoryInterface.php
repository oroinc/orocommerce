<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Order;

interface ApruveOrderBuilderFactoryInterface
{
    /**
     * @param string $merchantId
     * @param int    $amountCents
     * @param string $currency
     * @param array  $lineItems
     *
     * @return ApruveOrderBuilderInterface
     */
    public function create($merchantId, $amountCents, $currency, array $lineItems);
}
