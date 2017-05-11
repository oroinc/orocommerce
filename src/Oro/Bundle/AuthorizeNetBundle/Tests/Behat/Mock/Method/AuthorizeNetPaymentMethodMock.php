<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\Method;

use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class AuthorizeNetPaymentMethodMock extends AuthorizeNetPaymentMethod
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }
}
