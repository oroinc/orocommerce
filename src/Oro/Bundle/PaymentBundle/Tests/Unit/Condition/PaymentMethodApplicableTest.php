<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentMethodApplicable;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;

class PaymentMethodApplicableTest extends \PHPUnit\Framework\TestCase
{
    const METHOD = 'Method';

    /** @var PaymentMethodApplicable */
    protected $condition;

    /** @var ApplicablePaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodProvider;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this
            ->getMockBuilder(ApplicablePaymentMethodsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new PaymentMethodApplicable($this->paymentMethodProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('payment_method_applicable', $this->condition->getName());
    }

    public function testInitializeInvalidFirstArguments()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "payment_method" option');

        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testInitializeInvalidSecondArguments()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "context" option');

        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize(['payment_method'])
        );
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([self::METHOD, new \stdClass()])
        );
    }

    public function testEvaluate()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('getIdentifier')
            ->willReturn(self::METHOD);

        $this->paymentMethodProvider->expects(static::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([$paymentMethod]);

        $this->condition->initialize(['payment_method' => self::METHOD, 'context' => $context]);
        $this->assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluateNoMethod()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('getIdentifier')
            ->willReturn('another_method');

        $this->paymentMethodProvider->expects(static::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([$paymentMethod]);

        $this->condition->initialize(['payment_method' => self::METHOD, 'context' => $context]);
        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testEvaluateNoMethods()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        $this->paymentMethodProvider->expects(static::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([]);

        $this->condition->initialize(['payment_method' => self::METHOD, 'context' => $context]);
        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testToArray()
    {
        $this->condition->initialize(['payment_method' => self::METHOD, 'context' => new \stdClass()]);
        $result = $this->condition->toArray();

        $key = '@'.PaymentMethodApplicable::NAME;

        $this->assertIsArray($result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains(self::METHOD, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new ToStringStub();
        $options = ['payment_method' => self::METHOD, 'context' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [\'%s\', %s])',
                PaymentMethodApplicable::NAME,
                self::METHOD,
                $toStringStub
            ),
            $result
        );
    }
}
