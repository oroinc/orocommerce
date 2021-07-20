<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

interface FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
{
    public function create(
        ShippingLineItemCollectionInterface $lineItemCollection,
        FedexPackageSettingsInterface $packageSettings
    ): array;
}
