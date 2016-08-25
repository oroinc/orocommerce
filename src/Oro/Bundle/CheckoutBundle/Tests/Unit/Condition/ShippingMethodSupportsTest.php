<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Condition\ShippingMethodSupports;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodSupportsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const METHOD = 'Method';

    /** @var ShippingMethodSupports */
    protected $condition;

    /** @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingMethodRegistry;

    /** @var ShippingRulesProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingRulesProvider;

    /** @var  ShippingRuleConfiguration */
    protected $shippingRuleConfig;

    /** @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingContextProviderFactory;

    protected function setUp()
    {
        $this->shippingMethodRegistry = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry');

        $this->shippingRulesProvider = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextProviderFactory = $this
            ->getMockBuilder('Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingContextProviderFactory->expects(static::any())
            ->method('create')
            ->willReturn(new ShippingContextProvider([]));

        $this->shippingRuleConfig = $this->getEntity(
            'Oro\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration',
            [
                'id'     => 1,
                'method' => 'flat_rate',
                'type'   => 'flat_rate'
            ]
        );
        
        $this->condition = new ShippingMethodSupports(
            $this->shippingMethodRegistry,
            $this->shippingRulesProvider,
            $this->shippingContextProviderFactory
        );
    }

    protected function tearDown()
    {
        unset($this->condition, $this->shippingMethodRegistry);
    }

    public function testGetName()
    {
        static::assertEquals(ShippingMethodSupports::NAME, $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "entity" option
     */
    public function testInitializeInvalid()
    {
        static::assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testInitialize()
    {
        static::assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([
                'entity' => new Checkout(),
                'shipping_rule_config' => $this->shippingRuleConfig
            ])
        );
    }

    /**
     * @dataProvider evaluateProvider
     * @param string $rule
     * @param string $methodName
     * @param string $typeName
     * @param bool $expected
     */
    public function testEvaluate($rule, $methodName, $typeName, $expected)
    {
        $shippingRule = new ShippingRule();
        $shippingRule->setName('TetsRule')
            ->setPriority(10)
            ->addConfiguration($this->shippingRuleConfig);
        
        $rules = [
            'no' => [],
            'yes' => [10 => $shippingRule]
        ];
        
        $method = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $method->expects(static::any())
            ->method('getShippingTypes')
            ->willReturn(['per_order', 'per_item']);
        $this->shippingMethodRegistry->expects(static::any())->method('getShippingMethod')->willReturn($method);


        $this->shippingRulesProvider->expects(static::any())
            ->method('getApplicableShippingRules')
            ->willReturn($rules[$rule]);
        $checkout = new Checkout();
        $checkout->setShippingMethod($methodName)->setShippingMethodType($typeName);
        $this->condition->initialize([
            'entity' => $checkout,
            'shipping_rule_config' => $this->shippingRuleConfig
        ]);
        static::assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            'no_rules' => [
                'rules' => 'no',
                'method' => 'flat_rate',
                'type' => 'per_order',
                'expected' => false,
            ],
            'not_correct_method' => [
                'rules' => 'yes',
                'method' => 'flat_rule',
                'type' => 'per_order',
                'expected' => false,
            ],
            'correct_method_not_correct_type' => [
                'rules' => 'yes',
                'method' => 'flat_rate',
                'type' => null,
                'expected' => false,
            ],
            'correct_method_correct_type' => [
                'rules' => 'yes',
                'method' => 'flat_rate',
                'type' => 'per_order',
                'expected' => true,
            ],
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize([
            'entity' => $stdClass,
            'shipping_rule_config' => $this->shippingRuleConfig
        ]);
        $result = $this->condition->toArray();

        $key = '@' . ShippingMethodSupports::NAME;

        static::assertInternalType('array', $result);
        static::assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        static::assertInternalType('array', $resultSection);
        static::assertArrayHasKey('parameters', $resultSection);
        static::assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new ToStringStub();
        $options = ['entity' => $toStringStub, 'shipping_rule_config' => $this->shippingRuleConfig];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        static::assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                ShippingMethodSupports::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
