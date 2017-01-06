<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentMethodApplicable;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;

class PaymentMethodApplicableTest extends \PHPUnit_Framework_TestCase
{
    const METHOD = 'Method';

    /** @var PaymentMethodApplicable */
    protected $condition;

    /** @var PaymentMethodProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodProvider;

    protected function setUp()
    {
        $this->paymentMethodProvider = $this
            ->getMockBuilder(PaymentMethodProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new PaymentMethodApplicable($this->paymentMethodProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('payment_method_applicable', $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "payment_method" option
     */
    public function testInitializeInvalidFirstArguments()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "context" option
     */
    public function testInitializeInvalidSecondArguments()
    {
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

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('getType')
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

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('getType')
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

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
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
