<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment\ApruveShipmentFromPaymentContextFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;
use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Config\ApruveConfigRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Shipment\Factory\CreateShipmentRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\ShipmentPaymentAction;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ShipmentPaymentActionTest extends \PHPUnit_Framework_TestCase
{
    use LoggerAwareTraitTestTrait;

    const APRUVE_INVOICE_ID = 'sampleApruveOrderId';

    const RESPONSE_DATA = [
        'id' => 'sampleId',
    ];

    const REQUEST_DATA = [
        'method' => 'POST',
        'uri' => 'invoices/1/shipments',
        'data' => [
            'amount_cents' => 1000,
            'currency' => 'USD',
            'shippedAt' => '2027-04-15T10:12:27-05:00',
        ],
    ];

    const RETURN_SUCCESS = ['success' => true];
    const RETURN_ERROR = ['success' => false];

    /**
     * @var ApruveRestClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveRestClient;

    /**
     * @var CreateShipmentRequestFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $createShipmentRequestFactory;

    /**
     * @var ApruveConfigRestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveConfigRestClientFactory;

    /**
     * @var ApruveShipment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveShipment;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContext;

    /**
     * @var ApruveShipmentFromPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveShipmentFromPaymentContextFactory;

    /**
     * @var ShipmentPaymentAction
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

        $this->apruveShipment = $this->createMock(ApruveShipment::class);
        $this->apruveShipmentFromPaymentContextFactory
            = $this->createMock(ApruveShipmentFromPaymentContextFactoryInterface::class);

        $this->apruveRestClient = $this->createMock(ApruveRestClientInterface::class);
        $this->apruveConfigRestClientFactory
            = $this->createMock(ApruveConfigRestClientFactoryInterface::class);

        $this->createShipmentRequestFactory
            = $this->createMock(CreateShipmentRequestFactoryInterface::class);

        $this->paymentAction = new ShipmentPaymentAction(
            $this->paymentContextFactory,
            $this->apruveShipmentFromPaymentContextFactory,
            $this->apruveConfigRestClientFactory,
            $this->createShipmentRequestFactory
        );

        $this->setUpLoggerMock($this->paymentAction);
    }

    public function testExecute()
    {
        $isSuccessful = true;

        $this->mockPaymentContextFactory($this->paymentContext);
        $this->mockApruveShipmentFactory();
        $this->mockSourcePaymentTransaction(ApruvePaymentMethod::INVOICE, self::APRUVE_INVOICE_ID);
        $this->mockApruveShipmentRequest(self::REQUEST_DATA, self::APRUVE_INVOICE_ID);
        $this->mockApruveRestClient($this->mockRestResponse($isSuccessful, self::RESPONSE_DATA));

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setResponse')
            ->with(self::RESPONSE_DATA)
            ->willReturnSelf();

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

        $this->apruveShipmentFromPaymentContextFactory
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

        $this->apruveShipmentFromPaymentContextFactory
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

        $this->apruveShipmentFromPaymentContextFactory
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
        $this->mockApruveShipmentFactory();
        $this->mockSourcePaymentTransaction(ApruvePaymentMethod::INVOICE, self::APRUVE_INVOICE_ID);
        $this->mockApruveShipmentRequest(self::REQUEST_DATA, self::APRUVE_INVOICE_ID);
        $this->mockApruveRestClient($this->mockRestResponse($isSuccessful, self::RESPONSE_DATA));

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setResponse')
            ->with(self::RESPONSE_DATA);

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
        $this->mockApruveShipmentFactory();
        $this->mockSourcePaymentTransaction(ApruvePaymentMethod::INVOICE, self::APRUVE_INVOICE_ID);
        $this->mockApruveShipmentRequest(self::REQUEST_DATA, self::APRUVE_INVOICE_ID);

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

        static::assertSame(ShipmentPaymentAction::NAME, $actual);
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
     * @param string $apruveInvoiceId
     */
    private function mockApruveShipmentRequest(array $requestData, $apruveInvoiceId)
    {
        $apruveShipmentRequest = $this->createMock(ApruveRequestInterface::class);

        $apruveShipmentRequest
            ->expects(static::once())
            ->method('toArray')
            ->willReturn($requestData);

        $this->createShipmentRequestFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->apruveShipment, $apruveInvoiceId)
            ->willReturn($apruveShipmentRequest);
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
     * @return RestException
     */
    private function createRestException()
    {
        return new RestException();
    }

    private function mockApruveShipmentFactory()
    {
        $this->apruveShipmentFromPaymentContextFactory
            ->expects(static::once())
            ->method('createFromPaymentContext')
            ->with($this->paymentContext)
            ->willReturn($this->apruveShipment);
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
