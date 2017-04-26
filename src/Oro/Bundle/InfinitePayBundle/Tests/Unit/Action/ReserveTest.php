<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\ReservationRequestMapper;
use Oro\Bundle\InfinitePayBundle\Action\Mapper\ReservationResponseMapper;
use Oro\Bundle\InfinitePayBundle\Action\Mapper\ResponseMapperInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\AutomationProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\RequestMapperInterface;
use Oro\Bundle\InfinitePayBundle\Action\Reserve;
use Oro\Bundle\InfinitePayBundle\Gateway\GatewayInterface;
use Oro\Bundle\InfinitePayBundle\Gateway\SoapGateway;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\Provider\InfinitePayConfigProviderInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrderResponse;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseReservation;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * {@inheritdoc}
 */
class ReserveTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestMapperInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMapper;

    /**
     * @var ResponseMapperInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMapper;

    /**
     * @var InfinitePayConfigInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var InfinitePayConfigProviderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->config = $this->createMock(InfinitePayConfigInterface::class);

        $this->configProvider = $this->createMock(InfinitePayConfigProviderInterface::class);
        $this->configProvider
            ->expects(static::any())
            ->method('getPaymentConfig')
            ->willReturn($this->config);

        $this->requestMapper = $this->createMock(ReservationRequestMapper::class);
        $this->requestMapper
            ->expects(static::any())
            ->method('createRequestFromOrder')
            ->willReturn(new ReserveOrder());
        $this->responseMapper = new ReservationResponseMapper();
    }

    public function testExecuteSuccess()
    {
        $responseSuccess = $this->getResponseReservation();
        $responseSuccess->getResponse()->getResponseData()->setStatus('1');
        /** @var GatewayInterface|PHPUnit_Framework_MockObject_MockObject $gateway */
        $gateway = $this->createMock(GatewayInterface::class);
        $gateway
            ->expects(static::once())
            ->method('reserve')
            ->willReturn($responseSuccess);
        $actionReserve = new Reserve(
            $gateway,
            $this->configProvider
        );
        /** @var InvoiceDataProviderInterface|PHPUnit_Framework_MockObject_MockObject $invoiceDataProvider */
        $invoiceDataProvider = $this->createMock(InvoiceDataProviderInterface::class);
        $automationProvider = new AutomationProvider($invoiceDataProvider);

        $actionReserve->setRequestMapper($this->requestMapper);
        $actionReserve->setResponseMapper($this->responseMapper);
        $actionReserve->setAutomationProvider($automationProvider);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setTransactionOptions(
            ['additionalOptions' => []]
        );
        $order = new Order();
        $reserveResponse = $actionReserve->execute($paymentTransaction, $order);
        $this->assertArrayNotHasKey('successUrl', $reserveResponse);
        $this->assertTrue($reserveResponse['success']);
    }

    public function testExecuteFailure()
    {
        $responseFail = $this->getResponseReservation();
        $responseFail->getResponse()->getResponseData()->setStatus('0');
        /** @var GatewayInterface|PHPUnit_Framework_MockObject_MockObject $gateway */
        $gateway = $this->createMock(GatewayInterface::class);
        $gateway
            ->expects(static::once())
            ->method('reserve')
            ->willReturn($responseFail);
        $actionReserve = new Reserve(
            $gateway,
            $this->configProvider
        );
        /** @var InvoiceDataProviderInterface|PHPUnit_Framework_MockObject_MockObject $invoiceDataProvider */
        $invoiceDataProvider = $this->createMock(InvoiceDataProviderInterface::class);
        $automationProvider = new AutomationProvider($invoiceDataProvider);

        $actionReserve->setRequestMapper($this->requestMapper);
        $actionReserve->setResponseMapper($this->responseMapper);
        $actionReserve->setAutomationProvider($automationProvider);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setTransactionOptions(
            ['additionalOptions' => []]
        );
        $order = new Order();

        $reserveResponse = $actionReserve->execute($paymentTransaction, $order);
        $this->assertNull($reserveResponse['successUrl']);
        $this->assertFalse($reserveResponse['success']);
    }

    public function testExecuteSuccessAutoActivation()
    {
        $responseSuccess = $this->getResponseReservation();
        $responseSuccess->getResponse()->getResponseData()->setStatus('1');
        /** @var GatewayInterface|PHPUnit_Framework_MockObject_MockObject $gateway */
        $gateway = $this->createMock(GatewayInterface::class);
        $gateway
            ->expects(static::exactly(2))
            ->method('reserve')
            ->willReturn($responseSuccess);
        $this->config
            ->expects(static::exactly(2))
            ->method('isAutoActivateEnabled')
            ->willReturn(true);
        $actionReserve = new Reserve(
            $gateway,
            $this->configProvider
        );
        /** @var InvoiceDataProviderInterface|PHPUnit_Framework_MockObject_MockObject $invoiceDataProvider */
        $invoiceDataProvider = $this->createMock(InvoiceDataProvider::class);
        $automationProvider = new AutomationProvider($invoiceDataProvider);

        $actionReserve->setRequestMapper($this->requestMapper);
        $actionReserve->setResponseMapper($this->responseMapper);
        $actionReserve->setAutomationProvider($automationProvider);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setTransactionOptions(
            ['additionalOptions' => []]
        );
        $order = new Order();
        $reserveResponse = $actionReserve->execute($paymentTransaction, $order);
        $this->assertArrayNotHasKey('successUrl', $reserveResponse);
        $this->assertTrue($reserveResponse['success']);

        $reserveResponse = $actionReserve->execute($paymentTransaction, $order);
        $this->assertArrayNotHasKey('successKey', $reserveResponse);
        $this->assertTrue($reserveResponse['success']);
    }

    /**
     * @return ReserveOrderResponse
     */
    private function getResponseReservation()
    {
        $reserveOrderResponse = new ReserveOrderResponse();
        $responseReservation = new ResponseReservation();
        $reserveOrderResponse->setRESPONSE($responseReservation);
        $responseReservation->setResponseData(new ResponseData());

        return $reserveOrderResponse;
    }
}
