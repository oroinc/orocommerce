<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyRegistry;

class StrategyProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var StrategyRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $strategyRegistry;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var StrategyProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->strategyRegistry = $this->createMock(StrategyRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new StrategyProvider(
            $this->strategyRegistry,
            $this->configManager
        );
    }

    public function testGetActiveStrategy(): void
    {
        $alias = 'test';
        $strategy = $this->createMock(StrategyInterface::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_promotion.discount_strategy')
            ->willReturn($alias);

        $this->strategyRegistry->expects(self::once())
            ->method('getStrategyByAlias')
            ->with($alias)
            ->willReturn($strategy);

        self::assertSame($strategy, $this->provider->getActiveStrategy());
    }

    public function testGetActiveStrategyWhenStrategyNotFound(): void
    {
        $alias = 'test';

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_promotion.discount_strategy')
            ->willReturn($alias);

        $this->strategyRegistry->expects(self::once())
            ->method('getStrategyByAlias')
            ->with($alias)
            ->willReturn(null);

        self::assertNull($this->provider->getActiveStrategy());
    }
}
