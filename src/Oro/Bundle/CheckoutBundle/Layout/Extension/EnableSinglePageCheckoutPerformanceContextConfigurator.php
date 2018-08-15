<?php

namespace Oro\Bundle\CheckoutBundle\Layout\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class EnableSinglePageCheckoutPerformanceContextConfigurator implements ContextConfiguratorInterface
{
    const ENABLE_SINGLE_PAGE_CHECKOUT_PERFORMANCE_OPTION_NAME = 'enable_single_page_checkout_performance';

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
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired([self::ENABLE_SINGLE_PAGE_CHECKOUT_PERFORMANCE_OPTION_NAME])
            ->setAllowedTypes([self::ENABLE_SINGLE_PAGE_CHECKOUT_PERFORMANCE_OPTION_NAME => ['boolean']]);

        $context->set(
            self::ENABLE_SINGLE_PAGE_CHECKOUT_PERFORMANCE_OPTION_NAME,
            (bool) $this->configManager->get('oro_checkout.single_page_checkout_increase_performance')
        );
    }
}
