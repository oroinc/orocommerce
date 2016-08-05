<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Condition;

use OroB2B\Bundle\PaymentBundle\Condition\PaymentMethodRequiresVerification;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class PaymentMethodRequiresVerificationTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD_KEY = 'payment_method';

    /** @var array */
    protected $paymentTermData = [
        self::PAYMENT_METHOD_KEY => 'payment_term',
    ];

    /** @var array */
    protected $paymentMethodWithValidateData = [
        self::PAYMENT_METHOD_KEY => 'payment_method_with_validate',
    ];

    /** @var PaymentMethodRequiresVerification */
    protected $condition;

    /** @var PaymentMethodRegistry | \PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    public function setUp()
    {
        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');
        $this->condition = new PaymentMethodRequiresVerification($this->paymentMethodRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentMethodRequiresVerification::NAME, $this->condition->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize($this->paymentTermData)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInitializeWithException()
    {
        $this->condition->initialize([]);
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param array $data
     * @param bool $supportsData
     * @param bool $expected
     */
    public function testEvaluate(array $data, $requiresVerificationData, $expected)
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');

        /** @var PaymentMethodInterface | \PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('requiresVerification')
            ->willReturn($requiresVerificationData);

        $this->paymentMethodRegistry->expects($this->once())
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
            'payment_term' => [
                'data' => $this->paymentTermData,
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

    public function testEvaluateWithException()
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->will($this->throwException(new \InvalidArgumentException));

        $this->condition->initialize($this->paymentTermData);
        $this->assertFalse($this->condition->evaluate($context, $errors));
    }

    public function testToArray()
    {
        $this->condition->initialize($this->paymentTermData);
        $result = $this->condition->toArray();
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('@' . PaymentMethodRequiresVerification::NAME, $result);
        $resultSection = $result['@' . PaymentMethodRequiresVerification::NAME];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($this->paymentTermData[self::PAYMENT_METHOD_KEY], $resultSection['parameters']);
    }

    public function testCompile()
    {
        $this->condition->initialize($this->paymentTermData);
        $result = $this->condition->compile('');
        $this->assertContains(PaymentMethodRequiresVerification::NAME, $result);
        $this->assertContains($this->paymentTermData[self::PAYMENT_METHOD_KEY], $result);
    }
}
