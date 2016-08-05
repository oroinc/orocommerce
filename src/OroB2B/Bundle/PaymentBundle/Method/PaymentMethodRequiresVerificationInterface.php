<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

interface PaymentMethodRequiresVerificationInterface
{
    /**
     * @return bool
     */
    public function requiresVerification();
}
