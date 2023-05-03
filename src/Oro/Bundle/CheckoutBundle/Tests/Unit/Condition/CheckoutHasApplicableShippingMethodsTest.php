<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\CheckoutHasApplicableShippingMethods;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\AvailableShippingMethodCheckerInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class CheckoutHasApplicableShippingMethodsTest extends \PHPUnit\Framework\TestCase
{
    /** @var AvailableShippingMethodCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $availableShippingMethodChecker;

    /** @var CheckoutHasApplicableShippingMethods */
    private $condition;

    protected function setUp(): void
    {
        $this->availableShippingMethodChecker = $this->createMock(AvailableShippingMethodCheckerInterface::class);

        $this->condition = new CheckoutHasApplicableShippingMethods(
            $this->availableShippingMethodChecker
        );
    }

    public function testGetName(): void
    {
        self::assertEquals('checkout_has_applicable_shipping_methods', $this->condition->getName());
    }

    public function testInitializeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "checkout" option');

        $this->condition->initialize([]);
    }

    public function testInitialize(): void
    {
        self::assertSame(
            $this->condition,
            $this->condition->initialize(['Method', new \stdClass()])
        );
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(bool $hasAvailableShippingMethods, bool $expected): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->availableShippingMethodChecker->expects(self::once())
            ->method('hasAvailableShippingMethods')
            ->with(self::identicalTo($checkout))
            ->willReturn($hasAvailableShippingMethods);

        $this->condition->initialize(['checkout' => $checkout]);

        self::assertEquals($expected, $this->condition->evaluate([]));
    }

    public function evaluateProvider(): array
    {
        return [
            'no available shipping methods' => [
                'hasAvailableShippingMethods' => false,
                'expected' => false
            ],
            'have available shipping methods' => [
                'hasAvailableShippingMethods' => true,
                'expected' => true
            ],
        ];
    }

    public function testToArray(): void
    {
        $stdClass = new \stdClass();

        $this->condition->initialize(['checkout' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@checkout_has_applicable_shipping_methods';

        self::assertIsArray($result);
        self::assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        self::assertIsArray($resultSection);
        self::assertArrayHasKey('parameters', $resultSection);
        self::assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile(): void
    {
        $toStringStub = new ToStringStub();
        $options = ['checkout' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');

        self::assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                'checkout_has_applicable_shipping_methods',
                $toStringStub
            ),
            $result
        );
    }
}
