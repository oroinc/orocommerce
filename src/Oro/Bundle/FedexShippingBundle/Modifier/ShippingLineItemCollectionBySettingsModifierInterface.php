<?php

namespace Oro\Bundle\FedexShippingBundle\Modifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

interface ShippingLineItemCollectionBySettingsModifierInterface
{
    public function modify(
        ShippingLineItemCollectionInterface $lineItems,
        FedexIntegrationSettings $settings
    ): ShippingLineItemCollectionInterface;
}
