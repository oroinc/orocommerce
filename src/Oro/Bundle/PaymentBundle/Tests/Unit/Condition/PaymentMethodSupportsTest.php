<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentMethodSupports;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;

class PaymentMethodSupportsTest extends \PHPUnit\Framework\TestCase
{
    const PAYMENT_METHOD_KEY = 'payment_method';
    const ACTION_NAME_KEY = 'action';

    /** @var array */
    protected $paymentMethod = [
        self::PAYMENT_METHOD_KEY => 'payment_method',
        self::ACTION_NAME_KEY => 'authorize'
    ];

    /** @var array */
    protected $paymentMethodWithValidateData = [
        self::PAYMENT_METHOD_KEY => 'payment_method_with_validate',
        self::ACTION_NAME_KEY => 'validate'
    ];

    /** @var PaymentMethodSupports */
    protected $condition;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodProvider;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->condition = new PaymentMethodSupports($this->paymentMethodProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentMethodSupports::NAME, $this->condition->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize($this->paymentMethod)
        );
    }

    public function testInitializeWithException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->condition->initialize([]);
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param array $data
     * @param bool $supportsData
     * @param bool $expected
     */
    public function testEvaluate(array $data, $supportsData, $expected)
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');

        /** @var PaymentMethodInterface | \PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('supports')
            ->with($data[self::ACTION_NAME_KEY])
            ->willReturn($supportsData);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with($data[self::PAYMENT_METHOD_KEY])
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->with($data[self::PAYMENT_METHOD_KEY])
            ->willReturn($paymentMethod);

        $this->condition->initialize($data);
        $this->assertEquals($expected, $this->condition->evaluate($context, $errors));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            'payment_method' => [
                'data' => $this->paymentMethod,
                'supportsData' => false,
                'expected' => false
            ],
            'payment_method_with_validate' => [
                'data' => $this->paymentMethodWithValidateData,
                'supportsData' => true,
                'expected' => true
            ]
        ];
    }

    public function testEvaluateWithNotExistingPaymentMethod()
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->willReturn(false);

        $this->condition->initialize($this->paymentMethod);
        $this->assertFalse($this->condition->evaluate($context, $errors));
    }

    public function testToArray()
    {
        $this->condition->initialize($this->paymentMethod);
        $result = $this->condition->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('@' . PaymentMethodSupports::NAME, $result);
        $resultSection = $result['@' . PaymentMethodSupports::NAME];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($this->paymentMethod[self::PAYMENT_METHOD_KEY], $resultSection['parameters']);
        $this->assertContains($this->paymentMethod[self::ACTION_NAME_KEY], $resultSection['parameters']);
    }

    public function testCompile()
    {
        $this->condition->initialize($this->paymentMethod);
        $result = $this->condition->compile('');
        static::assertStringContainsString(PaymentMethodSupports::NAME, $result);
        static::assertStringContainsString($this->paymentMethod[self::PAYMENT_METHOD_KEY], $result);
        static::assertStringContainsString($this->paymentMethod[self::ACTION_NAME_KEY], $result);
    }
}
