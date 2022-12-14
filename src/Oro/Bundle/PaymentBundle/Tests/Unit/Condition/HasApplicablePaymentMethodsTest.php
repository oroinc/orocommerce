<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\HasApplicablePaymentMethods;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class HasApplicablePaymentMethodsTest extends \PHPUnit\Framework\TestCase
{
    private const METHOD = 'Method';

    /** @var ApplicablePaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var HasApplicablePaymentMethods */
    private $condition;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(ApplicablePaymentMethodsProvider::class);

        $this->condition = new HasApplicablePaymentMethods($this->paymentMethodProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('has_applicable_payment_methods', $this->condition->getName());
    }

    public function testInitializeInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "context" option');

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

    public function testEvaluate()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodProvider->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([$paymentMethod]);

        $this->condition->initialize(['payment_method' => self::METHOD, 'context' => $context]);
        $this->assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluateNoMethods()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        $this->paymentMethodProvider->expects(self::once())
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
