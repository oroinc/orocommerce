<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Stub\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalBundle\Tests\Functional\Stub\Method\ExpressCheckoutMethodStub;

class ExpressCheckoutMethodViewStub implements PaymentMethodViewInterface
{
    #[\Override]
    public function getOptions(PaymentContextInterface $context)
    {
        return [];
    }

    #[\Override]
    public function getBlock()
    {
        return '';
    }

    #[\Override]
    public function getLabel()
    {
        return 'Test';
    }

    #[\Override]
    public function getAdminLabel()
    {
        return 'Test';
    }

    #[\Override]
    public function getShortLabel()
    {
        return 'Test';
    }

    #[\Override]
    public function getPaymentMethodIdentifier()
    {
        return ExpressCheckoutMethodStub::TYPE;
    }
}
