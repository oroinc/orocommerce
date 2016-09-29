<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\ReservationResponseMapper;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrderResponse;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseReservation;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * {@inheritdoc}
 */
class ReservationResponseMapperTest extends \PHPUnit_Framework_TestCase
{
    protected $orderReference = 'test_order_ref';

    public function testMapResponseToPaymentTransactionSuccess()
    {
        $responseMapper = new ReservationResponseMapper();
        $paymentTransaction = new PaymentTransaction();
        $response = $this->getResponse($this->orderReference, '1');
        $actualPaymentTransaction = $responseMapper->mapResponseToPaymentTransaction($paymentTransaction, $response);
        $this->assertTrue($actualPaymentTransaction->isActive());
        $this->assertEquals($this->orderReference, $actualPaymentTransaction->getReference());
    }

    public function testMapResponseToPaymentTransactionFailure()
    {
        $responseMapper = new ReservationResponseMapper();
        $paymentTransaction = new PaymentTransaction();
        $response = $this->getResponse($this->orderReference, '0');
        $actualPaymentTransaction = $responseMapper->mapResponseToPaymentTransaction($paymentTransaction, $response);
        $this->assertFalse($actualPaymentTransaction->isActive());
        $this->assertEquals($this->orderReference, $actualPaymentTransaction->getReference());
    }

    /**
     * @param $orderReference
     * @param $status
     *
     * @return ReserveOrderResponse
     */
    private function getResponse($orderReference, $status)
    {
        $responseReservation = new ResponseReservation();
        $responseReservation->setResponseData((new ResponseData())->setRefNo($orderReference)->setStatus($status));

        return  (new ReserveOrderResponse())->setRESPONSE($responseReservation);
    }
}
