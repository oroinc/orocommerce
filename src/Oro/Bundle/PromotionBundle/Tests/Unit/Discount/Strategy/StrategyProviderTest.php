<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyRegistry;

class StrategyProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StrategyRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $strategyRegistry;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var StrategyProvider
     */
    private $provider;

    protected function setUp()
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
