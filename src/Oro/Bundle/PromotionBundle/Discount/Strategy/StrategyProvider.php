<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * This class is responsible for getting selected strategy from registry based on chosen strategy in system config
 */
class StrategyProvider
{
    const CONFIG_KEY = 'oro_promotion.discount_strategy';

    /**
     * @var StrategyRegistry
     */
    private $strategyRegistry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(
        StrategyRegistry $strategyRegistry,
        ConfigManager $configManager
    ) {
        $this->strategyRegistry = $strategyRegistry;
        $this->configManager = $configManager;
    }

    /**
     * @return null|StrategyInterface
     */
    public function getActiveStrategy()
    {
        $selectedStrategyAlias = $this->configManager->get(self::CONFIG_KEY);

        return $this->strategyRegistry->getStrategyByAlias($selectedStrategyAlias);
    }
}
