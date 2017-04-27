<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\AuthorizePaymentAction;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class AuthorizePaymentActionTest extends \PHPUnit_Framework_TestCase
{
    const RESPONSE = [ApruvePaymentMethod::PARAM_ORDER_ID => 100];
    const INITIAL_OPTIONS = ['some_option' => 'option_value'];
    const OPTIONS = [
        ApruvePaymentMethod::PARAM_ORDER_ID => 100,
        'some_option' => 'option_value',
    ];

    /**
     * @var AuthorizePaymentAction
     */
    private $paymentAction;

    /**
     * @var TransactionPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContextFactory;

    /**
     * @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTransaction;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->paymentContextFactory = $this->createMock(TransactionPaymentContextFactoryInterface::class);
        $this->paymentTransaction = $this->createMock(PaymentTransaction::class);
        $this->config = $this->createMock(ApruveConfigInterface::class);

        $this->paymentAction = new AuthorizePaymentAction($this->paymentContextFactory);
    }

    public function testExecute()
    {
        $this->paymentTransaction
            ->expects(static::once())
            ->method('getResponse')
            ->willReturn(self::RESPONSE);
        $this->paymentTransaction
            ->expects(static::once())
            ->method('getTransactionOptions')
            ->willReturn(self::INITIAL_OPTIONS);
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setTransactionOptions')
            ->with(self::OPTIONS);
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setSuccessful')
            ->with(true);
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setActive')
            ->with(true);
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setAction')
            ->with('authorize');
        $actual = $this->paymentAction->execute($this->config, $this->paymentTransaction);

        static::assertSame([], $actual);
    }

    public function testGetName()
    {
        $actual = $this->paymentAction->getName();

        static::assertSame(AuthorizePaymentAction::NAME, $actual);
    }
}
