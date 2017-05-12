<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method;

use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
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
     * @var SupportedCurrenciesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $supportedCurrenciesProvider;

    /**
     * @var PaymentActionExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentActionExecutor;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->supportedCurrenciesProvider = $this->createMock(SupportedCurrenciesProviderInterface::class);
        $this->paymentActionExecutor = $this->createMock(PaymentActionExecutor::class);

        $this->method = new ApruvePaymentMethod(
            $this->config,
            $this->supportedCurrenciesProvider,
            $this->paymentActionExecutor
        );
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
            [false, ApruvePaymentMethod::VALIDATE],
            [false, ApruvePaymentMethod::PURCHASE],
            [false, ApruvePaymentMethod::CHARGE],
        ];
    }

    /**
     * @dataProvider isApplicableDataProvider
     *
     * @param string $currency
     * @param bool $isSupported
     * @param bool $expectedResult
     */
    public function testIsApplicable($currency, $isSupported, $expectedResult)
    {
        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context
            ->method('getCurrency')
            ->willReturn($currency);

        $this->supportedCurrenciesProvider
            ->method('isSupported')
            ->with($currency)
            ->willReturn($isSupported);

        $actual = $this->method->isApplicable($context);
        static::assertSame($expectedResult, $actual);
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        return [
            'should be applicable if currency is supported' => ['USD', true, true],
            'should be inapplicable if currency is not supported' => ['EUR', false, false],
        ];
    }
}
