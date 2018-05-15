<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyRegistry;

class StrategyRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        /** @var StrategyInterface $strategy1 */
        $strategy1 = $this->createMock(StrategyInterface::class);
        /** @var StrategyInterface $strategy2 */
        $strategy2 = $this->createMock(StrategyInterface::class);

        $registry = new StrategyRegistry();
        $registry->addStrategy($strategy1, 'strategy1');
        $registry->addStrategy($strategy2, 'strategy2');

        $this->assertEquals(
            [
                'strategy1' => $strategy1,
                'strategy2' => $strategy2
            ],
            $registry->getStrategies()
        );
        $this->assertSame($strategy1, $registry->getStrategyByAlias('strategy1'));
        $this->assertNull($registry->getStrategyByAlias('unknown'));
    }
}
