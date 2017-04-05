<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor\PaymentActionExecutor;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class ApruvePaymentMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruvePaymentMethod
     */
    private $method;

    /**
     * @var PaymentActionExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentActionExecutor;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->paymentActionExecutor = $this->createMock(PaymentActionExecutor::class);

        $this->method = new ApruvePaymentMethod($this->config, $this->paymentActionExecutor);
    }

    public function testExecute()
    {
        /** @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject $paymentTransaction */
        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $action = 'some_action';

        $this->paymentActionExecutor
            ->expects($this->once())
            ->method('execute')
            ->with($action, $this->config, $paymentTransaction)
            ->willReturn([]);

        $actual = $this->method->execute($action, $paymentTransaction);

        static::assertSame([], $actual);
    }

    public function testGetIdentifier()
    {
        $identifier = 'id';

        $this->config->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        static::assertEquals($identifier, $this->method->getIdentifier());
    }

    /**
     * @param bool $expected
     * @param string $actionName
     *
     * @dataProvider supportsDataProvider
     */
    public function testSupports($expected, $actionName)
    {
        $this->paymentActionExecutor
            ->expects($this->once())
            ->method('supports')
            ->willReturnMap([
                [ApruvePaymentMethod::AUTHORIZE, true],
                [ApruvePaymentMethod::CAPTURE, true],
                [ApruvePaymentMethod::COMPLETE, true],
                [ApruvePaymentMethod::CANCEL, true],
                [ApruvePaymentMethod::VALIDATE, false],
                [ApruvePaymentMethod::PURCHASE, false],
                [ApruvePaymentMethod::CHARGE, false],
            ]);

        static::assertEquals($expected, $this->method->supports($actionName));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [true, ApruvePaymentMethod::AUTHORIZE],
            [true, ApruvePaymentMethod::CAPTURE],
            [true, ApruvePaymentMethod::COMPLETE],
            [true, ApruvePaymentMethod::CANCEL],
            [false, ApruvePaymentMethod::VALIDATE],
            [false, ApruvePaymentMethod::PURCHASE],
            [false, ApruvePaymentMethod::CHARGE],
        ];
    }

    public function testIsApplicable()
    {
        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        static::assertTrue($this->method->isApplicable($context));
    }
}
