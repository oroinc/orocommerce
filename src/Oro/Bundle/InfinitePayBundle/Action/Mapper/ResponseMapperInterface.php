<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseBodyInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface ResponseMapperInterface
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
    );
}
