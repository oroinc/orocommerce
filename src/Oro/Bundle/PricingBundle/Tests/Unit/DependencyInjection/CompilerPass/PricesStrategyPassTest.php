<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\PricesStrategyPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PricesStrategyPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var PricesStrategyPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new PricesStrategyPass();
    }

    public function testProcessNotStrategyRegister()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $strategyRegisterDef = $container->register('oro_pricing.pricing_strategy.strategy_register');

        $container->register('strategy_register1')
            ->addTag('oro_pricing.price_strategy', ['alias' => 'first']);
        $container->register('strategy_register2')
            ->addTag('oro_pricing.price_strategy', ['alias' => 'second']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['add', ['first', new Reference('strategy_register1')]],
                ['add', ['second', new Reference('strategy_register2')]]
            ],
            $strategyRegisterDef->getMethodCalls()
        );
    }

    public function testProcessNoTagged()
    {
        $container = new ContainerBuilder();
        $strategyRegisterDef = $container->register('oro_pricing.pricing_strategy.strategy_register');

        $this->compiler->process($container);

        self::assertSame([], $strategyRegisterDef->getMethodCalls());
    }

    public function testProcessWhenAliasAttributeIsMissing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Attribute "alias" is missing for "oro_pricing.price_strategy" tag at "strategy_register1" service'
        );

        $container = new ContainerBuilder();
        $container->register('oro_pricing.pricing_strategy.strategy_register');

        $container->register('strategy_register1')
            ->addTag('oro_pricing.price_strategy');

        $this->compiler->process($container);
    }
}
