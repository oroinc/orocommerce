<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTerm implements PaymentMethodInterface
{
    const TYPE = 'PaymentTerm';

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     */
    public function __construct(PaymentTermProvider $paymentTermProvider)
    {
        $this->paymentTermProvider = $paymentTermProvider;
    }

    /** {@inheritdoc} */
    public function execute(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setSuccessful(true);

        return [];
    }

    /** {@inheritdoc} */
    public function isEnabled()
    {
        return (bool)$this->paymentTermProvider->getCurrentPaymentTerm();
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }
}
