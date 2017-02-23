<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Stub\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PayPalBundle\Tests\Functional\Stub\Method\ExpressCheckoutMethodStub;

class ExpressCheckoutMethodViewProviderStub extends AbstractPaymentMethodViewProvider
{
    /**
     * {@inheritDoc}
     */
    protected function buildViews()
    {
        $this->addView(ExpressCheckoutMethodStub::TYPE, new ExpressCheckoutMethodViewStub());
    }
}
