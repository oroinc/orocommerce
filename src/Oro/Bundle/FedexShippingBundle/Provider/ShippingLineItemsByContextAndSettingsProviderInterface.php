<?php

namespace Oro\Bundle\FedexShippingBundle\Provider;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

interface ShippingLineItemsByContextAndSettingsProviderInterface
{
    /**
     * @param FedexIntegrationSettings $settings
     * @param ShippingContextInterface $context
     *
     * @return ShippingLineItemInterface[]
     */
    public function get(
        FedexIntegrationSettings $settings,
        ShippingContextInterface $context
    ): array;
}
