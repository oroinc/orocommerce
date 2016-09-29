<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\CaptureResponseMapper;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CaptureOrderResponse;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseCapture;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * {@inheritdoc}
 */
class CaptureResponseMapperTest extends \PHPUnit_Framework_TestCase
{
    protected $orderReference = 'test_order_ref';

    public function testMapResponseToPaymentTransactionSuccess()
    {
        $responseMapper = new CaptureResponseMapper();
        $paymentTransaction = new PaymentTransaction();
        $response = $this->getResponse($this->orderReference, '1');
        $actualPaymentTransaction = $responseMapper->mapResponseToPaymentTransaction($paymentTransaction, $response);
        $this->assertTrue($actualPaymentTransaction->isActive());
        $this->assertFalse($actualPaymentTransaction->isSuccessful());
        $this->assertEquals($this->orderReference, $actualPaymentTransaction->getReference());
    }

    public function testMapResponseToPaymentTransactionFailure()
    {
        $responseMapper = new CaptureResponseMapper();
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
     * @return CaptureOrderResponse
     */
    private function getResponse($orderReference, $status)
    {
        $responseCapture = new ResponseCapture();
        $responseCapture->setResponseData((new ResponseData())->setRefNo($orderReference)->setStatus($status));

        return (new CaptureOrderResponse())->setRESPONSE($responseCapture);
    }
}
