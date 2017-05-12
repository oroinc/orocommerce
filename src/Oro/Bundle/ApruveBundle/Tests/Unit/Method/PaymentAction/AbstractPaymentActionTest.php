<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Config\ApruveConfigRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

abstract class AbstractPaymentActionTest extends \PHPUnit_Framework_TestCase
{
    use LoggerAwareTraitTestTrait;

    const RESPONSE_DATA = [
        'id' => 'sampleId',
    ];

    const REQUEST_DATA = [
        'method' => 'POST',
        'uri' => 'sampleUri/1',
        'data' => [
            'amount_cents' => 1000,
            'currency' => 'USD',
            'shippedAt' => '2027-04-15T10:12:27-05:00',
        ],
    ];

    const RETURN_SUCCESS = ['successful' => true];
    const RETURN_ERROR = ['successful' => false];

    /**
     * @var string
     */
    protected $paymentActionName;

    /**
     * @var ApruveRestClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveRestClient;

    /**
     * @var ApruveConfigRestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveConfigRestClientFactory;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentContext;

    /**
     * @var TransactionPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentContextFactory;

    /**
     * @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransaction;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $apruveEntityFromPaymentContextFactory;

    /**
     * @var PaymentActionInterface
     */
    protected $paymentAction;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->paymentTransaction = $this->createMock(PaymentTransaction::class);

        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContextFactory = $this->createMock(TransactionPaymentContextFactoryInterface::class);

        $this->apruveRestClient = $this->createMock(ApruveRestClientInterface::class);
        $this->apruveConfigRestClientFactory
            = $this->createMock(ApruveConfigRestClientFactoryInterface::class);
    }

    public function testGetName()
    {
        $actual = $this->paymentAction->getName();

        static::assertSame($this->paymentActionName, $actual);
    }

    public function testExecuteWithoutPaymentContext()
    {
        $this->mockPaymentContextFactory();

        $this->apruveEntityFromPaymentContextFactory
            ->expects(static::never())
            ->method('createFromPaymentContext');

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(static::RETURN_ERROR, $actual);
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

        $this->apruveEntityFromPaymentContextFactory
            ->expects(static::never())
            ->method('createFromPaymentContext');

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(static::RETURN_ERROR, $actual);
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

        $this->apruveEntityFromPaymentContextFactory
            ->expects(static::never())
            ->method('createFromPaymentContext');

        $this->assertLoggerErrorMethodCalled();

        $actual = $this->paymentAction->execute($this->mockApruveConfig(), $this->paymentTransaction);

        static::assertSame(static::RETURN_ERROR, $actual);
    }

    protected function prepareExecuteWhenRestException()
    {
        $this->mockPaymentContextFactory($this->paymentContext);

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
            ->with(static::REQUEST_DATA)
            ->willReturnSelf();

        $this->assertLoggerErrorMethodCalled();
    }

    /**
     * @param string      $action
     * @param string|null $reference
     */
    protected function mockSourcePaymentTransaction($action, $reference)
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
    protected function mockApruveRestClient(RestResponseInterface $restResponse)
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
    protected function mockRestResponse($isSuccessful, array $responseData)
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
    protected function mockApruveConfig()
    {
        return $this->createMock(ApruveConfigInterface::class);
    }

    /**
     * @param bool $isSuccessful
     * @param bool $isActive
     */
    protected function mockPaymentTransactionResult($isSuccessful, $isActive)
    {
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setSuccessful')
            ->with($isSuccessful)
            ->willReturnSelf();

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setActive')
            ->with($isActive);
    }

    /**
     * @return RestException
     */
    protected function createRestException()
    {
        return new RestException();
    }

    /**
     * @param PaymentContextInterface|null $paymentContext
     */
    protected function mockPaymentContextFactory(PaymentContextInterface $paymentContext = null)
    {
        $this->paymentContextFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->paymentTransaction)
            ->willReturn($paymentContext);
    }
}
