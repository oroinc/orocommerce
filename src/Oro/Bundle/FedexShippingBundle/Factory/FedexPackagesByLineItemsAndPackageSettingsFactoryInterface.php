<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

interface FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
{
    /**
     * @param ShippingLineItemCollectionInterface $lineItemCollection
     * @param FedexPackageSettingsInterface       $packageSettings
     *
     * @return array
     */
    public function create(
        ShippingLineItemCollectionInterface $lineItemCollection,
        FedexPackageSettingsInterface $packageSettings
    ): array;
}
