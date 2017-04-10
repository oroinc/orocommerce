<?php

namespace Oro\Bundle\AuthorizeNetBundle\Settings\DataProvider;

interface PaymentActionsDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getPaymentActions();
}
