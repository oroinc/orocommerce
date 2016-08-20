<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentMethodSupports;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class PaymentMethodSupportsTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD_KEY = 'payment_method';
    const ACTION_NAME_KEY = 'action';

    /** @var array */
    protected $paymentTermData = [
        self::PAYMENT_METHOD_KEY => 'payment_term',
        self::ACTION_NAME_KEY => 'validate'
    ];

    /** @var array */
    protected $paymentMethodWithValidateData = [
        self::PAYMENT_METHOD_KEY => 'payment_method_with_validate',
        self::ACTION_NAME_KEY => 'validate'
    ];

    /** @var PaymentMethodSupports */
    protected $condition;

    /** @var PaymentMethodRegistry | \PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    public function setUp()
    {
        $this->paymentMethodRegistry = $this->getMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry');
        $this->condition = new PaymentMethodSupports($this->paymentMethodRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentMethodSupports::NAME, $this->condition->getName());
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
    public function testEvaluate(array $data, $supportsData, $expected)
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');

        /** @var PaymentMethodInterface | \PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('supports')
            ->with($data[self::ACTION_NAME_KEY])
            ->willReturn($supportsData);

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
        $this->assertArrayHasKey('@' . PaymentMethodSupports::NAME, $result);
        $resultSection = $result['@' . PaymentMethodSupports::NAME];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($this->paymentTermData[self::PAYMENT_METHOD_KEY], $resultSection['parameters']);
        $this->assertContains($this->paymentTermData[self::ACTION_NAME_KEY], $resultSection['parameters']);
    }

    public function testCompile()
    {
        $this->condition->initialize($this->paymentTermData);
        $result = $this->condition->compile('');
        $this->assertContains(PaymentMethodSupports::NAME, $result);
        $this->assertContains($this->paymentTermData[self::PAYMENT_METHOD_KEY], $result);
        $this->assertContains($this->paymentTermData[self::ACTION_NAME_KEY], $result);
    }
}
