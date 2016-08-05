<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Condition;

use OroB2B\Bundle\PaymentBundle\Condition\PaymentMethodRequiresVerification;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRequiresVerificationInterface;

class PaymentMethodRequiresVerificationTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD_KEY = 'payment_method';

    /** @var array */
    protected $paymentTermData = [
        self::PAYMENT_METHOD_KEY => 'payment_term',
    ];

    /** @var array */
    protected $paymentMethodRequiresVerificationData = [
        self::PAYMENT_METHOD_KEY => 'payment_method_requires_verification',
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
     * @dataProvider evaluateDataProvider
     * @param array $data
     * @param string $paymentMethodInterface
     * @param int $callsCount
     * @param bool $requiresVerificationData
     * @param bool $expected
     */
    public function testEvaluate(array $data, $paymentMethodInterface, $callsCount, $requiresVerificationData, $expected)
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');
        $paymentMethod = $this->getMock($paymentMethodInterface);

        $paymentMethod->expects($this->exactly($callsCount))
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
                'paymentMethod' => 'OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface',
                'callsCount' => 0,
                'requiresVerificationData' => false,
                'expected' => false
            ],
            'payment_method_requires_verification_false' => [
                'data' => $this->paymentMethodRequiresVerificationData,
                'paymentMethod' => 'OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRequiresVerificationInterface',
                'callsCount' => 1,
                'requiresVerificationData' => false,
                'expected' => false
            ],
            'payment_method_requires_verification_true' => [
                'data' => $this->paymentMethodRequiresVerificationData,
                'paymentMethod' => 'OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRequiresVerificationInterface',
                'callsCount' => 1,
                'requiresVerificationData' => true,
                'expected' => true
            ]
        ];
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
