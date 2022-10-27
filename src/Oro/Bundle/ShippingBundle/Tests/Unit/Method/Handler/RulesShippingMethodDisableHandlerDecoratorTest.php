<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Handler;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\Handler\RulesShippingMethodDisableHandlerDecorator;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class RulesShippingMethodDisableHandlerDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodDisableHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var ShippingMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var RulesShippingMethodDisableHandlerDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(ShippingMethodDisableHandlerInterface::class);
        $this->repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->decorator = new RulesShippingMethodDisableHandlerDecorator(
            $this->handler,
            $this->repository,
            $this->shippingMethodProvider
        );
    }

    /**
     * @dataProvider testHandleMethodDisableProvider
     */
    public function testHandleMethodDisable(string $disabledMethodId, array $configs, array $registryMap)
    {
        $this->handler->expects(self::once())
            ->method('handleMethodDisable')
            ->with($disabledMethodId);

        $registryMapValues = [];
        $methods = [];
        foreach ($registryMap as $methodId => $enabled) {
            $methods[$methodId] = $this->createMock(ShippingMethodInterface::class);
            $methods[$methodId]->expects(self::any())
                ->method('isEnabled')
                ->willReturn($enabled);
            $registryMapValues[] = [$methodId, $methods[$methodId]];
        }

        $rules = [];
        $enabledRules = [];
        foreach ($configs as $configName => $config) {
            $methodConfigs = [];
            foreach ($config['methods'] as $methodId) {
                $methodConfig = $this->createMock(ShippingMethodConfig::class);
                $methodConfig->expects(self::once())
                    ->method('getMethod')
                    ->willReturn($methodId);
                $methodConfigs[] = $methodConfig;
            }

            $rule = $this->createMock(Rule::class);
            $rule->expects(self::exactly($config['rule_disabled']))
                ->method('setEnabled')
                ->with(false);
            $rules[$configName] = $rule;

            $enabledRule = $this->createMock(ShippingMethodsConfigsRule::class);
            $enabledRule->expects(self::once())
                ->method('getMethodConfigs')
                ->willReturn($methodConfigs);
            $enabledRule->expects(self::any())
                ->method('getRule')
                ->willReturn($rules[$configName]);
            $enabledRules[] = $enabledRule;
        }

        $this->shippingMethodProvider->expects(self::any())
            ->method('getShippingMethod')
            ->willReturnMap($registryMapValues);

        $this->repository->expects(self::once())
             ->method('getEnabledRulesByMethod')
             ->willReturn($enabledRules);

        $this->decorator->handleMethodDisable($disabledMethodId);
    }

    public function testHandleMethodDisableProvider(): array
    {
        return [
            'a_few_methods' => [
                'methodId' => 'method1',
                'configs' => [
                    'config1' => ['methods' => ['method1', 'method2'], 'rule_disabled' => 1],
                    'config2' => ['methods' => ['method1', 'method3'], 'rule_disabled' => 0]
                ],
                'registry_map' => [
                    'method1' => true,
                    'method2' => false,
                    'method3' => true,
                ],
            ],
            'only_method' => [
                'methodId' => 'method1',
                'configs' => [
                    'config1' => ['methods' => ['method1'], 'rule_disabled' => 1],
                ],
                'registry_map' => [
                    'method1' => true,
                ],
            ],
        ];
    }
}
