<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides a discount strategy chosen in the system configuration.
 */
class StrategyProvider
{
    private StrategyRegistry $strategyRegistry;
    private ConfigManager $configManager;

    public function __construct(
        StrategyRegistry $strategyRegistry,
        ConfigManager $configManager
    ) {
        $this->strategyRegistry = $strategyRegistry;
        $this->configManager = $configManager;
    }

    public function getActiveStrategy(): ?StrategyInterface
    {
        return $this->strategyRegistry->getStrategyByAlias(
            $this->configManager->get('oro_promotion.discount_strategy')
        );
    }
}
