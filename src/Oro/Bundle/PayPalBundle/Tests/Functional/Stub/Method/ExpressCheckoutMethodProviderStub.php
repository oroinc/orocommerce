<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Stub\Method;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;

class ExpressCheckoutMethodProviderStub extends AbstractPaymentMethodProvider
{
    /**
     * {@inheritDoc}
     */
    protected function collectMethods()
    {
        $this->addMethod(ExpressCheckoutMethodStub::TYPE, new ExpressCheckoutMethodStub());
    }
}
