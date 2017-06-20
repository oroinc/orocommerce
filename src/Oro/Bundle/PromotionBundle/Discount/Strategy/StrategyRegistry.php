<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

class StrategyRegistry
{
    /**
     * @var array|StrategyInterface[]
     */
    private $strategies = [];

    /**
     * @param StrategyInterface $strategy
     * @param string $alias
     */
    public function addStrategy(StrategyInterface $strategy, $alias)
    {
        $this->strategies[$alias] = $strategy;
    }

    /**
     * @return array|StrategyInterface[]
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * @param string $alias
     * @return null|StrategyInterface
     */
    public function getStrategyByAlias($alias)
    {
        if (array_key_exists($alias, $this->strategies)) {
            return $this->strategies[$alias];
        }

        return null;
    }
}
