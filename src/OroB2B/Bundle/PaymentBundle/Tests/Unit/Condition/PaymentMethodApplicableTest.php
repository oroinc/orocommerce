<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Condition;

use OroB2B\Bundle\PaymentBundle\Condition\PaymentMethodApplicable;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentMethodApplicableTest extends \PHPUnit_Framework_TestCase
{
    const METHOD = 'Method';

    /** @var PaymentMethodApplicable */
    protected $condition;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    /** @var PaymentContextProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentContextProvider;

    /** @var array */
    protected $contextData = ['contextData' => 'data'];

    protected function setUp()
    {
        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');
        $this->paymentContextProvider = $this
            ->getMockBuilder('\OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn($this->contextData);

        $this->condition = new PaymentMethodApplicable($this->paymentMethodRegistry, $this->paymentContextProvider);
    }

    protected function tearDown()
    {
        unset($this->condition, $this->paymentMethodRegistry);
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
     * @expectedExceptionMessage Missing "entity" option
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
        $paymentMethod->expects($isEnabled ? $this->once() : $this->never())
            ->method('isApplicable')
            ->with($this->contextData)
            ->willReturn($isApplicable);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with(self::METHOD)
            ->willReturn($paymentMethod);

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

    public function testEvaluateWithException()
    {
        $context = [];

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->will($this->throwException(new \InvalidArgumentException));

        $this->condition->initialize(['payment_method' => self::METHOD, 'entity' => new \stdClass()]);
        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testToArray()
    {
        $this->condition->initialize(['payment_method' => self::METHOD, 'entity' => new \stdClass()]);
        $result = $this->condition->toArray();

        $key = '@' . PaymentMethodApplicable::NAME;

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
        $options = ['payment_method' => self::METHOD, 'entity' => $toStringStub];

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
