<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyRegistry;

class StrategyProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StrategyRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $strategyRegistry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var StrategyProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->strategyRegistry = $this->createMock(StrategyRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new StrategyProvider(
            $this->strategyRegistry,
            $this->configManager
        );
    }

    public function testGetActiveStrategy()
    {
        $alias = 'test';
        $strategy = $this->createMock(StrategyInterface::class);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(StrategyProvider::CONFIG_KEY)
            ->willReturn($alias);

        $this->strategyRegistry->expects($this->once())
            ->method('getStrategyByAlias')
            ->with($alias)
            ->willReturn($strategy);

        $this->assertSame($strategy, $this->provider->getActiveStrategy());
    }
}
