<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice\ApruveInvoiceFromPaymentContextFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice\ApruveInvoiceFromResponseFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Invoice\Factory\CreateInvoiceRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\InvoicePaymentAction;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class InvoicePaymentActionTest extends AbstractPaymentActionTest
{
    const APRUVE_ORDER_ID = 'sampleApruveOrderId';
    const APRUVE_INVOICE_ID = 'sampleApruveInvoiceId';

    const RETURN_ERROR = ['successful' => false, 'message' => 'oro.apruve.payment_transaction.invoice.result.error'];

    /**
     * @var CreateInvoiceRequestFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $createInvoiceRequestFactory;

    /**
     * @var ApruveInvoiceFromResponseFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveInvoiceFromResponseFactory;

    /**
     * @var ApruveInvoice|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveInvoice;

    /**
     * @var ApruveInvoiceFromPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveEntityFromPaymentContextFactory;

    /**
     * @var InvoicePaymentAction
     */
    protected $paymentAction;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->paymentActionName = InvoicePaymentAction::NAME;

        $this->apruveInvoice = $this->createMock(ApruveInvoice::class);
        $this->apruveEntityFromPaymentContextFactory
            = $this->createMock(ApruveInvoiceFromPaymentContextFactoryInterface::class);

        $this->apruveInvoiceFromResponseFactory
            = $this->createMock(ApruveInvoiceFromResponseFactoryInterface::class);

        $this->createInvoiceRequestFactory
            = $this->createMock(CreateInvoiceRequestFactoryInterface::class);

        $this->paymentAction = new InvoicePaymentAction(
            $this->paymentContextFactory,
            $this->apruveEntityFromPaymentContextFactory,
            $this->apruveInvoiceFromResponseFactory,
            $this->apruveConfigRestClientFactory,
            $this->createInvoiceRequestFactory
        );

        $this->setUpLoggerMock($this->paymentAction);
    }

    public function testExecute()
    {
        $isSuccessful = true;

        $this->mockPaymentContextFactory($this->paymentContext);
        $this->mockApruveInvoiceFactory();
        $this->mockSourcePaymentTransaction(PaymentMethodInterface::AUTHORIZE, self::APRUVE_ORDER_ID);
        $this->mockApruveInvoiceRequest(self::REQUEST_DATA, self::APRUVE_ORDER_ID);
        $this->mockApruveRestClient($this->mockRestResponse($isSuccessful, self::RESPONSE_DATA));

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setResponse')
            ->with(self::RESPONSE_DATA)
            ->willReturnSelf();

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setReference')
            ->with(self::APRUVE_INVOICE_ID);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setRequest')
            ->with(self::REQUEST_DATA)
            ->willReturnSelf();

        $this->mockPaymentTransactionResult($isSuccessful, $isSuccessful);

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_SUCCESS, $actual);
    }

    public function testExecuteWhenResponseIsNotSuccessful()
    {
        $isSuccessful = false;

        $this->mockPaymentContextFactory($this->paymentContext);
        $this->mockApruveInvoiceFactory();
        $this->mockSourcePaymentTransaction(PaymentMethodInterface::AUTHORIZE, self::APRUVE_ORDER_ID);
        $this->mockApruveInvoiceRequest(self::REQUEST_DATA, self::APRUVE_ORDER_ID);
        $this->mockApruveRestClient($this->mockRestResponse($isSuccessful, self::RESPONSE_DATA));

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setResponse')
            ->with(self::RESPONSE_DATA)
            ->willReturnSelf();

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setReference')
            ->with(self::APRUVE_INVOICE_ID);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setRequest')
            ->with(self::REQUEST_DATA)
            ->willReturnSelf();

        $this->mockPaymentTransactionResult($isSuccessful, $isSuccessful);

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
    }

    public function testExecuteWhenRestException()
    {
        $isSuccessful = false;

        $this->mockApruveInvoiceRequest(self::REQUEST_DATA, self::APRUVE_ORDER_ID);
        $this->mockApruveInvoiceFactory();
        $this->mockPaymentTransactionResult($isSuccessful, $isSuccessful);
        $this->mockSourcePaymentTransaction(PaymentMethodInterface::AUTHORIZE, self::APRUVE_ORDER_ID);

        $this->prepareExecuteWhenRestException();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
    }

    /**
     * @param RestResponseInterface|\PHPUnit_Framework_MockObject_MockObject $restResponse
     */
    protected function mockApruveRestClient(RestResponseInterface $restResponse)
    {
        parent::mockApruveRestClient($restResponse);

        $this->apruveInvoiceFromResponseFactory
            ->expects(static::once())
            ->method('createFromResponse')
            ->with($restResponse)
            ->willReturn($this->mockCreatedApruveInvoice(self::APRUVE_INVOICE_ID));
    }

    /**
     * @param array  $requestData
     * @param string $apruveOrderId
     */
    private function mockApruveInvoiceRequest(array $requestData, $apruveOrderId)
    {
        $apruveInvoiceRequest = $this->createMock(ApruveRequestInterface::class);

        $apruveInvoiceRequest
            ->expects(static::once())
            ->method('toArray')
            ->willReturn($requestData);

        $this->createInvoiceRequestFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->apruveInvoice, $apruveOrderId)
            ->willReturn($apruveInvoiceRequest);
    }

    /**
     * @param string $apruveInvoiceId
     *
     * @return ApruveInvoice|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCreatedApruveInvoice($apruveInvoiceId)
    {
        $apruveInvoice = $this->createMock(ApruveInvoice::class);
        $apruveInvoice
            ->expects(static::once())
            ->method('getId')
            ->willReturn($apruveInvoiceId);

        return $apruveInvoice;
    }

    private function mockApruveInvoiceFactory()
    {
        $this->apruveEntityFromPaymentContextFactory
            ->expects(static::once())
            ->method('createFromPaymentContext')
            ->with($this->paymentContext)
            ->willReturn($this->apruveInvoice);
    }
}
