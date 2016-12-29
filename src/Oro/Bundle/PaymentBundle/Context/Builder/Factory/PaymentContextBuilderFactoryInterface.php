<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;

interface PaymentContextBuilderFactoryInterface
{
    /**
     * @param string           $currency
     * @param Price            $subTotal
     * @param object           $sourceEntity
     * @param string           $sourceEntityId
     *
     * @return PaymentContextBuilderInterface
     */
    public function createPaymentContextBuilder(
        $currency,
        Price $subTotal,
        $sourceEntity,
        $sourceEntityId
    );
}
