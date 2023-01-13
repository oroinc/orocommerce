<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Condition;

use Oro\Bundle\ShippingBundle\Condition\HasApplicableShippingMethods;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class HasApplicableShippingMethodsTest extends \PHPUnit\Framework\TestCase
{
    private const METHOD = 'Method';

    /** @var ShippingPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceProvider;

    /** @var HasApplicableShippingMethods */
    private $condition;

    protected function setUp(): void
    {
        $this->shippingPriceProvider = $this->createMock(ShippingPriceProvider::class);

        $this->condition = new HasApplicableShippingMethods($this->shippingPriceProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('has_applicable_shipping_methods', $this->condition->getName());
    }

    public function testInitializeInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "shippingContext" option');

        $this->assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize([])
        );
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize([self::METHOD, new \stdClass()])
        );
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(ShippingMethodViewCollection $methods, bool $expected)
    {
        $this->shippingPriceProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->willReturn($methods);

        $this->condition->initialize(['shippingContext' => new ShippingContext([])]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    public function evaluateProvider(): array
    {
        return [
            'no_rules_no_methods' => [
                'methods' => new ShippingMethodViewCollection(),
                'expected' => false,
            ],
            'with_rules_no_methods' => [
                'methods' => new ShippingMethodViewCollection(),
                'expected' => false,
            ],
            'with_rules_and_methods' => [
                'methods' => (new ShippingMethodViewCollection())
                    ->addMethodView('flat_rate', [])
                    ->addMethodTypeView('flat_rate', 'flat_rate_1', []),
                'expected' => true,
            ],
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['shippingContext' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@has_applicable_shipping_methods';

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
                'has_applicable_shipping_methods',
                $toStringStub
            ),
            $result
        );
    }
}
