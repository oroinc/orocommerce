<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Condition;

use Oro\Bundle\ShippingBundle\Condition\ShippingMethodHasShippingRules;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ShippingMethodHasShippingRulesTest extends \PHPUnit\Framework\TestCase
{
    private const PROPERTY_PATH_NAME = 'testPropertyPath';

    /** @var ShippingMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var PropertyPathInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyPath;

    /** @var ShippingMethodHasShippingRules */
    private $shippingMethodHasShippingRulesCondition;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);

        $this->propertyPath = $this->createMock(PropertyPathInterface::class);
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->willReturn(self::PROPERTY_PATH_NAME);
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->willReturn([self::PROPERTY_PATH_NAME]);

        $this->shippingMethodHasShippingRulesCondition = new ShippingMethodHasShippingRules($this->repository);
    }

    public function testGetName()
    {
        $this->assertEquals(
            ShippingMethodHasShippingRules::NAME,
            $this->shippingMethodHasShippingRulesCondition->getName()
        );
    }

    public function testInitializeInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "method_identifier" option');

        $this->assertInstanceOf(
            ShippingMethodHasShippingRules::class,
            $this->shippingMethodHasShippingRulesCondition->initialize([])
        );
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            ShippingMethodHasShippingRules::class,
            $this->shippingMethodHasShippingRulesCondition->initialize(['method_identifier'])
        );
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(array $rules, bool $expected)
    {
        $this->repository->expects(self::once())
            ->method('getRulesByMethod')
            ->willReturn($rules);

        $this->shippingMethodHasShippingRulesCondition->initialize(['method_identifier']);
        $this->assertEquals($expected, $this->shippingMethodHasShippingRulesCondition->evaluate([]));
    }

    public function evaluateProvider(): array
    {
        return [
            'no_rules' => [
                'rules' => [],
                'expected' => false,
            ],
            'with_rules' => [
                'rules' => [
                    new ShippingMethodsConfigsRule(),
                    new ShippingMethodsConfigsRule(),
                ],
                'expected' => true,
            ],
        ];
    }

    public function testToArray()
    {
        $result = $this->shippingMethodHasShippingRulesCondition->initialize([$this->propertyPath])->toArray();

        $this->assertEquals(
            sprintf('$%s', self::PROPERTY_PATH_NAME),
            $result['@shipping_method_has_shipping_rules']['parameters'][0]
        );
    }

    public function testCompile()
    {
        $result = $this->shippingMethodHasShippingRulesCondition->compile('$factoryAccessor');

        self::assertStringContainsString('$factoryAccessor->create(\'shipping_method_has_shipping_rules\'', $result);
    }

    public function testSetContextAccessor()
    {
        $contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $this->shippingMethodHasShippingRulesCondition->setContextAccessor($contextAccessor);

        $this->assertInstanceOf(
            get_class($contextAccessor),
            ReflectionUtil::getPropertyValue($this->shippingMethodHasShippingRulesCondition, 'contextAccessor')
        );
    }
}
