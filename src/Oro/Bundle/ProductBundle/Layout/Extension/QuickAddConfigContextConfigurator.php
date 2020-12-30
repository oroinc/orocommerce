<?php

namespace Oro\Bundle\ProductBundle\Layout\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Sets "newQuickAddPageLayout" to context if the corresponding option is enabled in the system configuration.
 */
class QuickAddConfigContextConfigurator implements ContextConfiguratorInterface
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

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()->setDefault('newQuickAddPageLayout', false);
        $context->set(
            'newQuickAddPageLayout',
            $this->configManager->get('oro_product.new_quick_order_form')
        );
    }
}
