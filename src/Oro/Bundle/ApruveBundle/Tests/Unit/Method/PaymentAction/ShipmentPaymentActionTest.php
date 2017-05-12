<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment\ApruveShipmentFromPaymentContextFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment\ApruveShipmentFromResponseFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Shipment\Factory\CreateShipmentRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\ShipmentPaymentAction;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ShipmentPaymentActionTest extends AbstractPaymentActionTest
{
    const APRUVE_INVOICE_ID = 'sampleApruveOrderId';
    const APRUVE_SHIPMENT_ID = 'sampleApruveShipmentId';

    const RETURN_ERROR = ['successful' => false, 'message' => 'oro.apruve.payment_transaction.shipment.result.error'];

    /**
     * @var ApruveShipmentFromResponseFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveShipmentFromResponseFactory;

    /**
     * @var CreateShipmentRequestFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $createShipmentRequestFactory;

    /**
     * @var ApruveShipment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveShipment;

    /**
     * @var ApruveShipmentFromPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveEntityFromPaymentContextFactory;

    /**
     * @var ShipmentPaymentAction
     */
    protected $paymentAction;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->paymentActionName = ShipmentPaymentAction::NAME;

        $this->apruveShipment = $this->createMock(ApruveShipment::class);
        $this->apruveEntityFromPaymentContextFactory
            = $this->createMock(ApruveShipmentFromPaymentContextFactoryInterface::class);

        $this->apruveShipmentFromResponseFactory
            = $this->createMock(ApruveShipmentFromResponseFactoryInterface::class);

        $this->createShipmentRequestFactory
            = $this->createMock(CreateShipmentRequestFactoryInterface::class);

        $this->paymentAction = new ShipmentPaymentAction(
            $this->paymentContextFactory,
            $this->apruveEntityFromPaymentContextFactory,
            $this->apruveShipmentFromResponseFactory,
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
            ->method('setReference')
            ->with(self::APRUVE_SHIPMENT_ID);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setRequest')
            ->with(self::REQUEST_DATA)
            ->willReturnSelf();

        $this->mockPaymentTransactionResult($isSuccessful, false);

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_SUCCESS, $actual);
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
            ->with(self::RESPONSE_DATA)
            ->willReturnSelf();

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setReference')
            ->with(self::APRUVE_SHIPMENT_ID);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setRequest')
            ->with(self::REQUEST_DATA)
            ->willReturnSelf();

        $this->mockPaymentTransactionResult($isSuccessful, false);

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(self::RETURN_ERROR, $actual);
    }

    public function testExecuteWhenRestException()
    {
        $isSuccessful = false;

        $this->mockApruveShipmentRequest(self::REQUEST_DATA, self::APRUVE_INVOICE_ID);
        $this->mockApruveShipmentFactory();
        $this->mockPaymentTransactionResult($isSuccessful, false);
        $this->mockSourcePaymentTransaction(ApruvePaymentMethod::INVOICE, self::APRUVE_INVOICE_ID);
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

        $this->apruveShipmentFromResponseFactory
            ->expects(static::once())
            ->method('createFromResponse')
            ->with($restResponse)
            ->willReturn($this->mockCreatedApruveShipment(self::APRUVE_SHIPMENT_ID));
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
     * @param string $apruveShipmentId
     *
     * @return ApruveShipment|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCreatedApruveShipment($apruveShipmentId)
    {
        $apruveShipment = $this->createMock(ApruveShipment::class);
        $apruveShipment
            ->expects(static::once())
            ->method('getId')
            ->willReturn($apruveShipmentId);

        return $apruveShipment;
    }

    private function mockApruveShipmentFactory()
    {
        $this->apruveEntityFromPaymentContextFactory
            ->expects(static::once())
            ->method('createFromPaymentContext')
            ->with($this->paymentContext)
            ->willReturn($this->apruveShipment);
    }
}
