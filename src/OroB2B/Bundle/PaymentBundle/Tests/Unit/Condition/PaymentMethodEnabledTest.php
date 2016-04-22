<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Condition;

use OroB2B\Bundle\PaymentBundle\Condition\PaymentMethodEnabled;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentMethodEnabledTest extends \PHPUnit_Framework_TestCase
{
    const METHOD = 'Method';

    /** @var PaymentMethodEnabled */
    protected $condition;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    protected function setUp()
    {
        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');
        $this->condition = new PaymentMethodEnabled($this->paymentMethodRegistry);
    }

    protected function tearDown()
    {
        unset($this->condition, $this->paymentMethodRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals('payment_method_enabled', $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given
     */
    public function testInitializeInvalidArguments0()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 2 given
     */
    public function testInitializeInvalidArguments2()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize(['value1', 'value2'])
        );
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize(['value'])
        );
    }

    /**
     * @dataProvider evaluateProvider
     * @param bool $expected
     */
    public function testEvaluate($expected)
    {
        $context = [];

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('isEnabled')
            ->willReturn($expected);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with(self::METHOD)
            ->willReturn($paymentMethod);

        $this->condition->initialize([self::METHOD]);
        $this->assertEquals($expected, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            [
                'expected' => true,
            ],
            [
                'expected' => false,
            ],
        ];
    }

    public function testEvaluateWithException()
    {
        $context = [];

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->will($this->throwException(new \InvalidArgumentException));

        $this->condition->initialize([self::METHOD]);
        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testToArray()
    {
        $this->condition->initialize([self::METHOD]);
        $result = $this->condition->toArray();

        $key = '@' . PaymentMethodEnabled::NAME;

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains(self::METHOD, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $options = [self::METHOD];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf('$factory->create(\'%s\', [\'%s\'])', PaymentMethodEnabled::NAME, self::METHOD),
            $result
        );
    }
}
