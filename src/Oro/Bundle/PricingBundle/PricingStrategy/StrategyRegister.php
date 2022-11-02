<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class StrategyRegister
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var PriceCombiningStrategyInterface[]
     */
    protected $strategies = [];

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return PriceCombiningStrategyInterface
     * @throws \InvalidArgumentException
     */
    public function getCurrentStrategy()
    {
        $currentAlias = $this->configManager->get('oro_pricing.price_strategy');

        return $this->get($currentAlias);
    }

    /**
     * @param string $alias
     * @param PriceCombiningStrategyInterface $strategy
     */
    public function add($alias, PriceCombiningStrategyInterface $strategy)
    {
        $this->strategies[$alias] = $strategy;
    }

    /**
     * @param $alias
     * @return PriceCombiningStrategyInterface
     * @throws \InvalidArgumentException
     */
    public function get($alias)
    {
        if (!isset($this->strategies[$alias])) {
            throw new \InvalidArgumentException(sprintf('Pricing strategy named "%s" does not exist.', $alias));
        }

        return $this->strategies[$alias];
    }

    /**
     * @return PriceCombiningStrategyInterface[]
     */
    public function getStrategies()
    {
        return $this->strategies;
    }
}
