<?php

namespace Oro\Bundle\CheckoutBundle\Layout\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EnableSinglePageCheckoutPerformanceContextConfigurator implements ContextConfiguratorInterface
{
    const ENABLE_SINGLE_PAGE_CHECKOUT_PERFORMANCE_OPTION_NAME = 'enable_single_page_checkout_performance';

    /** @var RequestStack */
    private $requestStack;

    /** @var ConfigManager */
    private $configManager;


    /**
     * @param RequestStack $requestStack
     * @param ConfigManager $configManager
     */
    public function __construct(RequestStack $requestStack, ConfigManager $configManager)
    {
        $this->requestStack = $requestStack;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $context->getResolver()->setDefined(self::ENABLE_SINGLE_PAGE_CHECKOUT_PERFORMANCE_OPTION_NAME);

        $context->set(
            self::ENABLE_SINGLE_PAGE_CHECKOUT_PERFORMANCE_OPTION_NAME,
            $this->configManager->get('oro_checkout.single_page_checkout_increase_performance')
        );
    }
}
