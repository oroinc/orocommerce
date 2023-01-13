<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\CheckoutHasApplicableShippingMethods;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class CheckoutHasApplicableShippingMethodsTest extends \PHPUnit\Framework\TestCase
{
    private const METHOD = 'Method';

    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingMethodsProvider;

    /** @var CheckoutHasApplicableShippingMethods */
    private $condition;

    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        $this->condition = new CheckoutHasApplicableShippingMethods(
            $this->checkoutShippingMethodsProvider
        );
    }

    public function testGetName()
    {
        $this->assertEquals('checkout_has_applicable_shipping_methods', $this->condition->getName());
    }

    public function testInitializeInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "checkout" option');

        $this->assertInstanceOf(AbstractCondition::class, $this->condition->initialize([]));
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
        $this->checkoutShippingMethodsProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->willReturn($methods);

        $this->condition->initialize(['checkout' => new Checkout()]);
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
        $this->condition->initialize(['checkout' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@checkout_has_applicable_shipping_methods';

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
        $options = ['checkout' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                'checkout_has_applicable_shipping_methods',
                $toStringStub
            ),
            $result
        );
    }
}
