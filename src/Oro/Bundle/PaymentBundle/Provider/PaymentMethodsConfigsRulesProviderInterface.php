<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

interface PaymentMethodsConfigsRulesProviderInterface
{
    /**
     * @param PaymentContextInterface $context
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getFilteredPaymentMethodsConfigsRegardlessDestination(PaymentContextInterface $context);

    /**
     * @param PaymentContextInterface $context
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getFilteredPaymentMethodsConfigs(PaymentContextInterface $context);
}
