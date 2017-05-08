<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice\ApruveInvoiceFromPaymentContextFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice\ApruveInvoiceFromResponseFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;
use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Config\ApruveConfigRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Invoice\Factory\CreateInvoiceRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\InvoicePaymentAction;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class InvoicePaymentActionTest extends \PHPUnit_Framework_TestCase
{
    use LoggerAwareTraitTestTrait;

    const APRUVE_ORDER_ID = 'sampleApruveOrderId';
    const APRUVE_INVOICE_ID = 'sampleApruveInvoiceId';

    const RESPONSE_DATA = [
        'id' => self::APRUVE_INVOICE_ID,
    ];

    const REQUEST_DATA = [
        'method' => 'POST',
        'uri' => 'orders/1/invoices',
        'data' => [
            'amount_cents' => 1000,
            'currency' => 'USD',
            'invoice_items' => [
                [
                    'title' => 'Sample title',
                    'amount_cents' => 1000,
                    'currency' => 'USD',
                    'quantity' => 1,
                ]
            ],
        ],
    ];

    const RETURN_SUCCESS = ['success' => true];
    const RETURN_ERROR = ['success' => false, 'message' => 'oro.apruve.payment_transaction.invoice.result.error'];

    /**
     * @var ApruveRestClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveRestClient;

    /**
     * @var CreateInvoiceRequestFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $createInvoiceRequestFactory;

    /**
     * @var ApruveConfigRestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveConfigRestClientFactory;

    /**
     * @var ApruveInvoiceFromResponseFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveInvoiceFromResponseFactory;

    /**
     * @var ApruveInvoice|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveInvoice;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContext;

    /**
     * @var ApruveInvoiceFromPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveInvoiceFromPaymentContextFactory;

    /**
     * @var InvoicePaymentAction
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->paymentTransaction = $this->createMock(PaymentTransaction::class);

        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContextFactory = $this->createMock(TransactionPaymentContextFactoryInterface::class);

        $this->apruveInvoice = $this->createMock(ApruveInvoice::class);
        $this->apruveInvoiceFromPaymentContextFactory
            = $this->createMock(ApruveInvoiceFromPaymentContextFactoryInterface::class);

        $this->apruveInvoiceFromResponseFactory
            = $this->createMock(ApruveInvoiceFromResponseFactoryInterface::class);

        $this->apruveRestClient = $this->createMock(ApruveRestClientInterface::class);
        $this->apruveConfigRestClientFactory
            = $this->createMock(ApruveConfigRestClientFactoryInterface::class);

        $this->createInvoiceRequestFactory
            = $this->createMock(CreateInvoiceRequestFactoryInterface::class);

        $this->paymentAction = new InvoicePaymentAction(
            $this->paymentContextFactory,
            $this->apruveInvoiceFromPaymentContextFactory,
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

        $this->mockPaymentTransactionResult($isSuccessful);

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_SUCCESS, $actual);
    }

    public function testExecuteWithoutPaymentContext()
    {
        $this->mockPaymentContextFactory();

        $this->apruveInvoiceFromPaymentContextFactory
            ->expects(static::never())
            ->method('createFromPaymentContext');

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
    }

    public function testExecuteWithoutSourcePaymentTransaction()
    {
        $transactionId = 1234;

        $this->mockPaymentContextFactory($this->paymentContext);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('getId')
            ->willReturn($transactionId);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('getSourcePaymentTransaction')
            ->willReturn(null);

        $this->apruveInvoiceFromPaymentContextFactory
            ->expects(static::never())
            ->method('createFromPaymentContext');

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
    }

    public function testExecuteWhenInvalidSourcePaymentTransaction()
    {
        $transactionId = 1234;

        $this->mockPaymentContextFactory($this->paymentContext);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('getId')
            ->willReturn($transactionId);

        $this->mockSourcePaymentTransaction('invalid_action', null);

        $this->apruveInvoiceFromPaymentContextFactory
            ->expects(static::never())
            ->method('createFromPaymentContext');

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
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

        $this->mockPaymentTransactionResult($isSuccessful);

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
    }

    public function testExecuteWhenRestException()
    {
        $isSuccessful = false;

        $this->mockPaymentContextFactory($this->paymentContext);
        $this->mockApruveInvoiceFactory();
        $this->mockSourcePaymentTransaction(PaymentMethodInterface::AUTHORIZE, self::APRUVE_ORDER_ID);
        $this->mockApruveInvoiceRequest(self::REQUEST_DATA, self::APRUVE_ORDER_ID);

        $this->apruveConfigRestClientFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->mockApruveConfig())
            ->willReturn($this->apruveRestClient);

        $this->apruveRestClient
            ->expects(static::once())
            ->method('execute')
            ->willThrowException($this->createRestException());

        $this->paymentTransaction
            ->expects(static::never())
            ->method('setResponse');

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setRequest')
            ->with(self::REQUEST_DATA)
            ->willReturnSelf();

        $this->mockPaymentTransactionResult($isSuccessful);

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
    }

    public function testGetName()
    {
        $actual = $this->paymentAction->getName();

        static::assertSame(InvoicePaymentAction::NAME, $actual);
    }

    /**
     * @param string      $action
     * @param string|null $reference
     */
    private function mockSourcePaymentTransaction($action, $reference)
    {
        $sourcePaymentTransaction = $this->createMock(PaymentTransaction::class);
        $sourcePaymentTransaction
            ->expects(static::once())
            ->method('getAction')
            ->willReturn($action);

        $sourcePaymentTransaction
            ->expects(static::any())
            ->method('getReference')
            ->willReturn($reference);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('getSourcePaymentTransaction')
            ->willReturn($sourcePaymentTransaction);
    }

    /**
     * @param RestResponseInterface|\PHPUnit_Framework_MockObject_MockObject $restResponse
     */
    private function mockApruveRestClient(RestResponseInterface $restResponse)
    {
        $this->apruveConfigRestClientFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->mockApruveConfig())
            ->willReturn($this->apruveRestClient);

        $this->apruveRestClient
            ->expects(static::once())
            ->method('execute')
            ->willReturn($restResponse);

        $this->apruveInvoiceFromResponseFactory
            ->expects(static::once())
            ->method('createFromResponse')
            ->with($restResponse)
            ->willReturn($this->mockCreatedApruveInvoice(self::APRUVE_INVOICE_ID));
    }

    /**
     * @param bool  $isSuccessful
     * @param array $responseData
     *
     * @return RestResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockRestResponse($isSuccessful, array $responseData)
    {
        $restResponse = $this->createMock(RestResponseInterface::class);
        $restResponse
            ->expects(static::once())
            ->method('isSuccessful')
            ->willReturn($isSuccessful);

        $restResponse
            ->expects(static::once())
            ->method('json')
            ->willReturn($responseData);

        return $restResponse;
    }

    /**
     * @return ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockApruveConfig()
    {
        return $this->createMock(ApruveConfigInterface::class);
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
     * @param bool $isSuccessful
     */
    private function mockPaymentTransactionResult($isSuccessful)
    {
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setSuccessful')
            ->with($isSuccessful)
            ->willReturnSelf();

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setActive')
            ->with($isSuccessful);
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

    /**
     * @return RestException
     */
    private function createRestException()
    {
        return new RestException();
    }

    private function mockApruveInvoiceFactory()
    {
        $this->apruveInvoiceFromPaymentContextFactory
            ->expects(static::once())
            ->method('createFromPaymentContext')
            ->with($this->paymentContext)
            ->willReturn($this->apruveInvoice);
    }

    /**
     * @param PaymentContextInterface|null $paymentContext
     */
    private function mockPaymentContextFactory(PaymentContextInterface $paymentContext = null)
    {
        $this->paymentContextFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->paymentTransaction)
            ->willReturn($paymentContext);
    }
}
