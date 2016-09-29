<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseBodyInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class ActivationResponseMapper implements ResponseMapperInterface
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
        $paymentTransaction->setSuccessful($response->getResponse()->getResponseData()->getStatus() === '1');

        return $paymentTransaction;
    }
}
