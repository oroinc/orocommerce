<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseBodyInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class ReservationResponseMapper implements ResponseMapperInterface
{
    /**
     * @param PaymentTransaction    $paymentTransaction
     * @param ResponseBodyInterface $response
     *
     * @return PaymentTransaction
     */
    public function mapResponseToPaymentTransaction(
        PaymentTransaction $paymentTransaction,
        ResponseBodyInterface $response
    ) {
        $status = $response->getResponse()->getResponseData()->getStatus();
        $paymentTransaction->setActive($status === '1' ? true : false);
        $paymentTransaction->setReference($response->getResponse()->getResponseData()->getRefNo());

        return $paymentTransaction;
    }
}
