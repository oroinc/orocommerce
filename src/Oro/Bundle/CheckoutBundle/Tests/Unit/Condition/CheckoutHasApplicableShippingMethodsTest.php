<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\CheckoutHasApplicableShippingMethods;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutHasApplicableShippingMethodsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const METHOD = 'Method';

    /** @var CheckoutHasApplicableShippingMethods */
    protected $condition;

    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutShippingMethodsProviderMock;

    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProviderMock = $this
            ->getMockBuilder(CheckoutShippingMethodsProviderInterface::class)
            ->getMock();

        $this->condition = new CheckoutHasApplicableShippingMethods(
            $this->checkoutShippingMethodsProviderMock
        );
    }

    public function testGetName()
    {
        $this->assertEquals(CheckoutHasApplicableShippingMethods::NAME, $this->condition->getName());
    }

    public function testInitializeInvalid()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "checkout" option');

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
     *
     * @param array $methods
     * @param bool $expected
     */
    public function testEvaluate($methods, $expected)
    {
        $this->checkoutShippingMethodsProviderMock->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->willReturn($methods);

        $this->condition->initialize(['checkout' => new Checkout()]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
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

        $key = '@'.CheckoutHasApplicableShippingMethods::NAME;

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
                CheckoutHasApplicableShippingMethods::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
