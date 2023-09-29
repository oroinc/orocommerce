<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Describes a factory that creates FedEx packages by collection of {@see ShippingLineItem}
 * and {@see FedexPackageSettingsInterface}.
 */
interface FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
{
    /**
     * @param Collection<ShippingLineItem> $lineItemCollection
     * @param FedexPackageSettingsInterface $packageSettings
     *
     * @return array
     */
    public function create(
        Collection $lineItemCollection,
        FedexPackageSettingsInterface $packageSettings
    ): array;
}
