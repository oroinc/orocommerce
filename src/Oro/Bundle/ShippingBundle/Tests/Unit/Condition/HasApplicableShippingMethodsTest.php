<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Condition;

use Oro\Bundle\ShippingBundle\Condition\HasApplicableShippingMethods;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
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


    protected function setUp()
    {
        $this->shippingMethodRegistry = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry');

        $this->shippingPriceProvider = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new HasApplicableShippingMethods(
            $this->shippingMethodRegistry,
            $this->shippingPriceProvider
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
     * @expectedExceptionMessage Missing "shippingContext" option
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
     * @param array $methods
     * @param bool $expected
     */
    public function testEvaluate($methods, $expected)
    {
        $method = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $this->shippingMethodRegistry->expects($this->any())->method('getShippingMethod')->willReturn($method);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getApplicableMethodsWithTypesData')
            ->willReturn($methods);

        $this->condition->initialize(['shippingContext' => new ShippingContext([])]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            'no_rules_no_methods' => [
                'methods' => [],
                'expected' => false,
            ],
            'with_rules_no_methods' => [
                'methods' => [],
                'expected' => false,
            ],
            'with_rules_and_methods' => [
                'methods' => ['flat_rate'],
                'expected' => true,
            ],
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['shippingContext' => $stdClass]);
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
        $options = ['shippingContext' => $toStringStub];

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
