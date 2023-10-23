<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

/**
 * Describes a factory that creates FedEx packages by collection of {@see ShippingLineItem}
 * and {@see FedexPackageSettingsInterface}.
 */
interface FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
{
    public function create(
        ShippingLineItemCollectionInterface $lineItemCollection,
        FedexPackageSettingsInterface $packageSettings
    ): array;
}
