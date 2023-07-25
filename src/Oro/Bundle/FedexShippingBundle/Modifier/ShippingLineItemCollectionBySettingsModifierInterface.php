<?php

namespace Oro\Bundle\FedexShippingBundle\Modifier;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Describes a Modifier that modifies Shipping Line Item collection by {@see FedexIntegrationSettings}.
 */
interface ShippingLineItemCollectionBySettingsModifierInterface
{
    /**
     * @param Collection<ShippingLineItem> $shippingLineItems
     * @param FedexIntegrationSettings $settings
     *
     * @return Collection<ShippingLineItem>
     */
    public function modify(
        Collection $shippingLineItems,
        FedexIntegrationSettings $settings
    ): Collection;
}
