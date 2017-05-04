<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Invoice;

interface ApruveInvoiceBuilderFactoryInterface
{
    /**
     * @param int    $amountCents
     * @param string $currency
     * @param array  $lineItems
     *
     * @return ApruveInvoiceBuilderInterface
     */
    public function create($amountCents, $currency, array $lineItems);
}
