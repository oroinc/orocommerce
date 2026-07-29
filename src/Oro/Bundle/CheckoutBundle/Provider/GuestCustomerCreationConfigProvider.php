<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides the config value that controls when a guest Customer/CustomerUser is created during checkout.
 */
class GuestCustomerCreationConfigProvider
{
    public function __construct(private readonly ConfigManager $configManager)
    {
    }

    public function isDeferredToOrderCreation(): bool
    {
        return (bool) $this->configManager->get('oro_checkout.defer_guest_customer_creation_to_order');
    }
}
