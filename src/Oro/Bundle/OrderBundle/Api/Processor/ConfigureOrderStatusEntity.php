<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Switches to the internal order status
 * when "Enable External Status Management" configuration option is disabled.
 */
class ConfigureOrderStatusEntity implements ProcessorInterface
{
    private OrderConfigurationProviderInterface $configurationProvider;

    public function __construct(OrderConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            return;
        }

        $context->getResult()->addHint('HINT_ENUM_OPTION', Order::INTERNAL_STATUS_CODE);
    }
}
