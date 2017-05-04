<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\CapturePaymentAction;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor\PaymentActionExecutorInterface;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class CapturePaymentActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveConfig;

    /**
     * @var TransactionPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionPaymentContextFactory;

    /**
     * @var PaymentActionExecutorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentActionExecutor;

    /**
     * @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTransaction;

    /**
     * @var CapturePaymentAction
     */
    private $paymentAction;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->paymentTransaction = $this->createMock(PaymentTransaction::class);

        $this->transactionPaymentContextFactory = $this->createMock(TransactionPaymentContextFactoryInterface::class);
        $this->paymentActionExecutor = $this->createMock(PaymentActionExecutorInterface::class);

        $this->apruveConfig = $this->createMock(ApruveConfigInterface::class);

        $this->paymentAction = new CapturePaymentAction(
            $this->transactionPaymentContextFactory,
            $this->paymentActionExecutor
        );
    }

    public function testExecute()
    {
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setAction')
            ->with(ApruvePaymentMethod::INVOICE);

        $this->paymentActionExecutor
            ->expects(static::once())
            ->method('execute')
            ->with(ApruvePaymentMethod::INVOICE, $this->apruveConfig, $this->paymentTransaction);

        $this->paymentAction->execute($this->apruveConfig, $this->paymentTransaction);
    }

    public function testGetName()
    {
        $actual = $this->paymentAction->getName();

        static::assertSame(CapturePaymentAction::NAME, $actual);
    }
}
