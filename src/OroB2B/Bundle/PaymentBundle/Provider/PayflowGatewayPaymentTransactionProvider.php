<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Collections\Criteria;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PayflowGatewayPaymentTransactionProvider
{
    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(PaymentTransactionProvider $paymentTransactionProvider)
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param object $object
     * @param string $paymentMethod
     * @return PaymentTransaction|null
     */
    public function getZeroAmountTransaction($object, $paymentMethod)
    {
        return $this->paymentTransactionProvider->getPaymentTransaction(
            $object,
            [
                'amount' => 0,
                'active' => true,
                'successful' => true,
                'action' => PaymentMethodInterface::AUTHORIZE,
                'paymentMethod' => (string)$paymentMethod,
            ],
            ['id' => Criteria::DESC]
        );
    }
}
