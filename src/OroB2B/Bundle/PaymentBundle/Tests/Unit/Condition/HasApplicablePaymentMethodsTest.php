<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Condition;

use OroB2B\Bundle\PaymentBundle\Condition\HasApplicablePaymentMethods;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class HasApplicablePaymentMethodsTest extends \PHPUnit_Framework_TestCase
{
    const METHOD = 'Method';

    /** @var HasApplicablePaymentMethods */
    protected $condition;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    /** @var PaymentContextProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentContextProvider;

    protected function setUp()
    {
        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');
        $this->paymentContextProvider = $this
            ->getMockBuilder('\OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn([]);

        $this->condition = new HasApplicablePaymentMethods($this->paymentMethodRegistry, $this->paymentContextProvider);
    }

    protected function tearDown()
    {
        unset($this->condition, $this->paymentMethodRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals('get_payment_methods', $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "entity" option
     */
    public function testInitializeInvalid()
    {
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

    /**
     * @dataProvider evaluateProvider
     * @param bool $isEnabled
     * @param bool $isApplicable
     * @param bool $expected
     */
    public function testEvaluate($isEnabled, $isApplicable, $expected)
    {
        $context = [];

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())->method('isEnabled')->willReturn($isEnabled);
        $paymentMethod->expects($this->any())->method('isApplicable')->willReturn($isApplicable);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        $this->condition->initialize(['payment_method' => self::METHOD, 'entity' => new \stdClass()]);
        $this->assertEquals($expected, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            [
                '$isEnabled' => true,
                '$isApplicable' => false,
                '$expected' => false,
            ],
            [
                '$isEnabled' => false,
                '$isApplicable' => false,
                '$expected' => false,
            ],
            [
                '$isEnabled' => false,
                '$isApplicable' => true,
                '$expected' => false,
            ],
            [
                '$isEnabled' => true,
                '$isApplicable' => true,
                '$expected' => true,
            ],
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['entity' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@'.HasApplicablePaymentMethods::NAME;

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new ToStringStub();
        $options = ['entity' => $toStringStub];

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
