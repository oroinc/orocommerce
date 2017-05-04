<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Shipment;

class ApruveShipmentBuilderFactory implements ApruveShipmentBuilderFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create($amountCents, $currency, $shippedAt)
    {
        return new ApruveShipmentBuilder($amountCents, $currency, $shippedAt);
    }
}
