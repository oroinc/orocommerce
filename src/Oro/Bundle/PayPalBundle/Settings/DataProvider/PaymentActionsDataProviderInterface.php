<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

interface PaymentActionsDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getPaymentActions();
}
