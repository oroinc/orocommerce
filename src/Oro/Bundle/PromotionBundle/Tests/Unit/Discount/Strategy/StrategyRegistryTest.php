<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class StrategyRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testRegistry()
    {
        /** @var StrategyInterface $strategy1 */
        $strategy1 = $this->createMock(StrategyInterface::class);
        /** @var StrategyInterface $strategy2 */
        $strategy2 = $this->createMock(StrategyInterface::class);

        $container = TestContainerBuilder::create()
            ->add('strategy1', $strategy1)
            ->add('strategy2', $strategy2)
            ->getContainer($this);

        $registry = new StrategyRegistry(
            ['strategy1', 'strategy2'],
            $container
        );

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
