<?php

namespace Oro\Bundle\CheckoutBundle\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Sets "newCheckoutPageLayout" to context if the corresponding option is enabled in the system configuration.
 */
class CheckoutAwareContextConfigurator implements ContextConfiguratorInterface
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
        if (!$this->isApplicable($context)) {
            return;
        }

        $context->getResolver()->setDefault('newCheckoutPageLayout', false);
        $context->set(
            'newCheckoutPageLayout',
            $this->configManager->get('oro_checkout.use_new_layout_for_checkout_page')
        );
    }

    /**
     * @param ContextInterface $context
     * @return bool
     */
    private function isApplicable(ContextInterface $context): bool
    {
        $data = $context->data();

        return ($data->has('checkout') && $data->get('checkout') instanceof CheckoutInterface) ||
            ($data->has('entity') && $data->get('entity') instanceof CheckoutInterface);
    }
}
