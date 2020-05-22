<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\HasApplicablePaymentMethods;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;

class HasApplicablePaymentMethodsTest extends \PHPUnit\Framework\TestCase
{
    const METHOD = 'Method';

    /** @var HasApplicablePaymentMethods */
    protected $condition;

    /** @var ApplicablePaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodProvider;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this
            ->getMockBuilder(ApplicablePaymentMethodsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new HasApplicablePaymentMethods($this->paymentMethodProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('has_applicable_payment_methods', $this->condition->getName());
    }

    public function testInitializeInvalid()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "context" option');

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

    public function testEvaluate()
    {
        $context = $this->getMockBuilder(PaymentContextInterface::class)
            ->getMockForAbstractClass();

        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodProvider->expects(static::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([$paymentMethod]);

        $this->condition->initialize(['payment_method' => self::METHOD, 'context' => $context]);
        $this->assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluateNoMethods()
    {
        $context = $this->getMockBuilder(PaymentContextInterface::class)
            ->getMockForAbstractClass();

        $this->paymentMethodProvider->expects(static::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([]);

        $this->condition->initialize(['payment_method' => self::METHOD, 'context' => $context]);
        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['context' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@'.HasApplicablePaymentMethods::NAME;

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
        $options = ['context' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                HasApplicablePaymentMethods::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
