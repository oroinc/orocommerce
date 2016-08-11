<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Condition\HasApplicableShippingMethods;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class HasApplicableShippingMethodsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const METHOD = 'Method';

    /** @var HasApplicableShippingMethods */
    protected $condition;

    /** @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingMethodRegistry;

    /** @var ShippingRulesProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingRulesProvider;

    /** @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingContextProviderFactory;

    protected function setUp()
    {
        $this->shippingMethodRegistry = $this->getMock('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry');

        $this->shippingRulesProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextProviderFactory = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingContextProviderFactory->expects(static::any())
            ->method('create')
            ->willReturn(new ShippingContextProvider([]));

        $this->condition = new HasApplicableShippingMethods(
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
        $this->assertEquals('has_applicable_shipping_methods', $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "entity" option
     */
    public function testInitializeInvalid()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([self::METHOD, new \stdClass()])
        );
    }

    /**
     * @dataProvider evaluateProvider
     * @param array $rules
     * @param bool $expected
     */
    public function testEvaluate($rules, $expected)
    {
        $method = $this->getMock('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $this->shippingMethodRegistry->expects($this->any())->method('getShippingMethod')->willReturn($method);


        $this->shippingRulesProvider->expects($this->any())
            ->method('getApplicableShippingRules')
            ->willReturn($rules);

        $this->condition->initialize(['entity' => new Checkout()]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        $shippingConfig = $this->getEntity(
            'OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration',
            [
                'id'     => 1,
                'method' => 'flat_rate',
                'type'   => 'per_order'
            ]
        );

        $shippingRule = new ShippingRule();
        $shippingRule->setName('TetsRule')
            ->setPriority(10)
            ->addConfiguration($shippingConfig);
        
        return [
            'no_rules' => [
                'rules' => [],
                'expected' => false,
            ],
            'with_rules' => [
                'rules' => [10 => $shippingRule],
                'expected' => true,
            ],
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['entity' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@' . HasApplicableShippingMethods::NAME;

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new ToStringStub();
        $options = ['entity' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                HasApplicableShippingMethods::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
