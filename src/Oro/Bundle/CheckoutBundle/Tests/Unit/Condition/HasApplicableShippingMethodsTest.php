<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\HasApplicableShippingMethods;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class HasApplicableShippingMethodsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const METHOD = 'Method';

    /** @var HasApplicableShippingMethods */
    protected $condition;

    /** @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingMethodRegistry;

    /** @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingPriceProvider;

    /** @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingContextProviderFactory;

    protected function setUp()
    {
        $this->shippingMethodRegistry = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry');

        $this->shippingPriceProvider = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextProviderFactory = $this
            ->getMockBuilder('Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingContextProviderFactory->expects(static::any())
            ->method('create')
            ->willReturn(new ShippingContext([]));

        $this->condition = new HasApplicableShippingMethods(
            $this->shippingMethodRegistry,
            $this->shippingPriceProvider,
            $this->shippingContextProviderFactory
        );
    }

    protected function tearDown()
    {
        unset($this->condition, $this->shippingMethodRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(HasApplicableShippingMethods::NAME, $this->condition->getName());
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
        $method = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $this->shippingMethodRegistry->expects($this->any())->method('getShippingMethod')->willReturn($method);


        $this->shippingPriceProvider->expects($this->any())
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
        $methodConfig = $this->getEntity(
            'Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig',
            [
                'id'     => 1,
                'method' => 'flat_rate'
            ]
        );

        $shippingRule = new ShippingRule();
        $shippingRule->setName('TetsRule')
            ->setPriority(10)
            ->addMethodConfig($methodConfig);
        
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
