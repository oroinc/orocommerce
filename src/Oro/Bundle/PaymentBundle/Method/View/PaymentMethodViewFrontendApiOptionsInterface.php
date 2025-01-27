<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * This interface should be implemented by payment method views ({@see PaymentMethodViewInterface})
 * that need to provide additional options for "available payment methods" storefront API.
 */
interface PaymentMethodViewFrontendApiOptionsInterface
{
    public function getFrontendApiOptions(PaymentContextInterface $context): array;
}
