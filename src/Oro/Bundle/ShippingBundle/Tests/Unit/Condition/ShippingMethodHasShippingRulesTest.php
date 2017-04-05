<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Condition;

use Oro\Bundle\ShippingBundle\Condition\ShippingMethodHasShippingRules;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ShippingMethodHasShippingRulesTest extends \PHPUnit_Framework_TestCase
{
    const PROPERTY_PATH_NAME = 'testPropertyPath';

    /**
     * @var ShippingMethodsConfigsRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var PropertyPathInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $propertyPath;

    /**
     * @var ShippingMethodHasShippingRules
     */
    protected $shippingMethodHasShippingRulesCondition;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(ShippingMethodsConfigsRuleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->propertyPath = $this->createMock(PropertyPathInterface::class);
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue(self::PROPERTY_PATH_NAME));
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue([self::PROPERTY_PATH_NAME]));

        $this->shippingMethodHasShippingRulesCondition = new ShippingMethodHasShippingRules($this->repository);
    }

    public function testGetName()
    {
        $this->assertEquals(
            ShippingMethodHasShippingRules::NAME,
            $this->shippingMethodHasShippingRulesCondition->getName()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing "method_identifier" option
     */
    public function testInitializeInvalid()
    {
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
     *
     * @param ShippingMethodsConfigsRule[] $rules
     * @param bool                         $expected
     */
    public function testEvaluate($rules, $expected)
    {
        $this->repository->expects(static::once())
            ->method('getRulesByMethod')
            ->willReturn($rules);

        $this->shippingMethodHasShippingRulesCondition->initialize(['method_identifier']);
        $this->assertEquals($expected, $this->shippingMethodHasShippingRulesCondition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
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

        $this->assertContains('$factoryAccessor->create(\'shipping_method_has_shipping_rules\'', $result);
    }

    public function testSetContextAccessor()
    {
        /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject $contextAccessor * */
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingMethodHasShippingRulesCondition->setContextAccessor($contextAccessor);

        $reflection = new \ReflectionProperty(
            get_class($this->shippingMethodHasShippingRulesCondition),
            'contextAccessor'
        );
        $reflection->setAccessible(true);

        $this->assertInstanceOf(
            get_class($contextAccessor),
            $reflection->getValue($this->shippingMethodHasShippingRulesCondition)
        );
    }
}
