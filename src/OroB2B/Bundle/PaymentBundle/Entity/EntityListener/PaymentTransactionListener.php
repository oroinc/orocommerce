<?php

namespace OroB2B\Bundle\PaymentBundle\Entity\EntityListener;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PaymentTransactionListener
{
    /** @var Mcrypt */
    protected $crypt;

    /**
     * @param Mcrypt $crypt
     */
    public function __construct(Mcrypt $crypt)
    {
        $this->crypt = $crypt;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function preFlush(PaymentTransaction $paymentTransaction)
    {
        $data = $paymentTransaction->getData();
        if (!$data) {
            return;
        }

        $paymentTransaction->setData(
            $this->crypt->encryptData($data)
        );
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function postLoad(PaymentTransaction $paymentTransaction)
    {
        $data = $paymentTransaction->getData();
        if (!$data) {
            return;
        }

        $paymentTransaction->setData(
            $this->crypt->decryptData($data)
        );
    }
}
