<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\PricingStrategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;

class StrategyRegisterTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $configManager = self::createMock(ConfigManager::class);
        $configManager->method('get')->willReturn(MergePricesCombiningStrategy::NAME);
        $register = new StrategyRegister($configManager);
        $strategy = self::createMock(PriceCombiningStrategyInterface::class);
        $register->add(MergePricesCombiningStrategy::NAME, $strategy);
        $this->assertSame($strategy, $register->getCurrentStrategy());
        $this->assertSame([MergePricesCombiningStrategy::NAME => $strategy], $register->getStrategies());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Pricing strategy named "merge_by_priority" does not exist.
     */
    public function testInvalidArguments()
    {
        $configManager = self::createMock(ConfigManager::class);
        $configManager->method('get')->willReturn(MergePricesCombiningStrategy::NAME);
        $register = new StrategyRegister($configManager);
        $register->getCurrentStrategy();
    }
}
