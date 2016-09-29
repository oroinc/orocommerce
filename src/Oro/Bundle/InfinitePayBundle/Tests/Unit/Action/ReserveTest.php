<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\ReservationRequestMapper;
use Oro\Bundle\InfinitePayBundle\Action\Mapper\ReservationResponseMapper;
use Oro\Bundle\InfinitePayBundle\Action\Mapper\ResponseMapperInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\AutomationProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\RequestMapperInterface;
use Oro\Bundle\InfinitePayBundle\Action\Reserve;
use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Gateway\SoapGateway;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrderResponse;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseReservation;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * {@inheritdoc}
 */
class ReserveTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestMapperInterface
     */
    protected $requestMapper;

    /**
     * @var ResponseMapperInterface
     */
    protected $responseMapper;

    /**
     * @var InfinitePayConfigInterface
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->config = $this->getMockBuilder(InfinitePayConfig::class)->disableOriginalConstructor()->getMock();
        $this->requestMapper = $this
            ->getMockBuilder(ReservationRequestMapper::class)->disableOriginalConstructor()->getMock();
        $this->requestMapper->method('createRequestFromOrder')->willReturn(new ReserveOrder());
        $this->responseMapper = new ReservationResponseMapper();
    }

    public function testExecuteSuccess()
    {
        $responseSuccess = $this->getResponseReservation();
        $responseSuccess->getResponse()->getResponseData()->setStatus('1');
        $gateway = $this->getMockBuilder(SoapGateway::class)->disableOriginalConstructor()->getMock();
        $gateway->method('reserve')->willReturn($responseSuccess);
        $actionReserve = new Reserve(
            $gateway,
            $this->config
        );
        $invoiceDataProvider = $this
            ->getMockBuilder(InvoiceDataProvider::class)->disableOriginalConstructor()->getMock();
        $automationProvider = new AutomationProvider($this->config, $invoiceDataProvider);

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
        $gateway = $this->getMockBuilder(SoapGateway::class)->disableOriginalConstructor()->getMock();
        $gateway->method('reserve')->willReturn($responseFail);
        $actionReserve = new Reserve(
            $gateway,
            $this->config
        );
        $invoiceDataProvider = $this
            ->getMockBuilder(InvoiceDataProvider::class)->disableOriginalConstructor()->getMock();
        $automationProvider = new AutomationProvider($this->config, $invoiceDataProvider);

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
        $gateway = $this->getMockBuilder(SoapGateway::class)->disableOriginalConstructor()->getMock();
        $gateway->method('reserve')->willReturn($responseSuccess);
        $configAutoActivation =
            $this->getMockBuilder(InfinitePayConfig::class)->disableOriginalConstructor()->getMock();
        $configAutoActivation->method('isAutoActivationActive')->willReturn(true);
        $actionReserve = new Reserve(
            $gateway,
            $configAutoActivation
        );
        $invoiceDataProvider = $this
            ->getMockBuilder(InvoiceDataProvider::class)->disableOriginalConstructor()->getMock();
        $automationProvider = new AutomationProvider($this->config, $invoiceDataProvider);

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
