<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Condition;

use Oro\Bundle\ShippingBundle\Condition\HasApplicableShippingMethods;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class HasApplicableShippingMethodsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const METHOD = 'Method';

    /** @var HasApplicableShippingMethods */
    protected $condition;

    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $shippingMethodProvider;

    /** @var ShippingPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $shippingPriceProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->shippingPriceProvider = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new HasApplicableShippingMethods(
            $this->shippingMethodProvider,
            $this->shippingPriceProvider
        );
    }

    protected function tearDown(): void
    {
        unset($this->condition, $this->shippingMethodProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(HasApplicableShippingMethods::NAME, $this->condition->getName());
    }

    public function testInitializeInvalid()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "shippingContext" option');

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
        $method = $this->createMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $this->shippingMethodProvider->expects($this->any())->method('getShippingMethod')->willReturn($method);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
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

        $this->assertIsArray($result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertIsArray($resultSection);
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
