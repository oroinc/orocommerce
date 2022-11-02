<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Psr\Container\ContainerInterface;

/**
 * The registry of discount strategies.
 */
class StrategyRegistry
{
    /** @var string[] */
    private $strategyAliases;

    /** @var ContainerInterface */
    private $strategyContainer;

    /**
     * @param string[]           $strategyAliases
     * @param ContainerInterface $strategyContainer
     */
    public function __construct(array $strategyAliases, ContainerInterface $strategyContainer)
    {
        $this->strategyAliases = $strategyAliases;
        $this->strategyContainer = $strategyContainer;
    }

    /**
     * @return StrategyInterface[] [alias => strategy, ...]
     */
    public function getStrategies(): array
    {
        $strategies = [];
        foreach ($this->strategyAliases as $alias) {
            $strategies[$alias] = $this->strategyContainer->get($alias);
        }

        return $strategies;
    }

    public function getStrategyByAlias(string $alias): ?StrategyInterface
    {
        if (!$this->strategyContainer->has($alias)) {
            return null;
        }

        return $this->strategyContainer->get($alias);
    }
}
