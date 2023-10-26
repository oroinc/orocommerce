<?php

namespace Oro\Bundle\FedexShippingBundle\Builder;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

/**
 * Describes a builder of shipping packages based on Shipping Line Items.
 */
interface ShippingPackagesByLineItemBuilderInterface
{
    public function init(FedexPackageSettingsInterface $settings);

    public function addLineItem(ShippingLineItemInterface $lineItem): bool;

    /**
     * @return ShippingPackageOptionsInterface[]
     */
    public function getResult(): array;
}
