<?php

namespace Oro\Bundle\ProductBundle\Layout\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Sets "microdata_without_prices_disabled" to context the corresponding option from the system configuration.
 * Component added back for theme layout BC from version 5.0
 */
class ProductSEOContextConfigurator implements ContextConfiguratorInterface
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()->setDefault('microdata_without_prices_disabled', false);
        $context->set(
            'microdata_without_prices_disabled',
            $this->configManager->get('oro_product.microdata_without_prices_disabled')
        );
    }
}
