<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Condition;

use OroB2B\Bundle\PaymentBundle\Condition\PaymentMethodEnabled;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentMethodEnabledTest extends \PHPUnit_Framework_TestCase
{
    const METHOD_1 = 'Method1';
    const METHOD_2 = 'Method2';

    /** @var PaymentMethodEnabled */
    protected $condition;

    /** @var PaymentMethodRegistry | \PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    public function setUp()
    {
        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');
        $this->condition = new PaymentMethodEnabled($this->paymentMethodRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentMethodEnabled::NAME, $this->condition->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testEvaluate()
    {
        $options = [self::METHOD_1, self::METHOD_2];
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');

        /** @var PaymentMethodInterface | \PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with(self::METHOD_1)
            ->willReturn($paymentMethod);

        $this->condition->initialize($options);
        $this->assertTrue($this->condition->evaluate($context, $errors));
    }

    public function testEvaluateWithException()
    {
        $options = [];
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->will($this->throwException(new \InvalidArgumentException));

        $this->condition->initialize($options);
        $this->assertFalse($this->condition->evaluate($context, $errors));
    }

    public function testToArray()
    {
        $options = [self::METHOD_1, self::METHOD_2];

        $this->condition->initialize($options);
        $result = $this->condition->toArray();
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('@' . PaymentMethodEnabled::NAME, $result);
        $resultSection = $result['@' . PaymentMethodEnabled::NAME];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains(self::METHOD_1, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $options = [self::METHOD_1, self::METHOD_2];

        $this->condition->initialize($options);
        $result = $this->condition->compile('');
        $this->assertContains(PaymentMethodEnabled::NAME, $result);
        $this->assertContains(self::METHOD_1, $result);
    }
}
