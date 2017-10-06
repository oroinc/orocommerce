<?php

namespace Oro\Bundle\PaymentBundle\RuleFiltration;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

interface MethodsConfigsRulesFiltrationServiceInterface
{
    /**
     * @param PaymentMethodsConfigsRule[] $paymentMethodsConfigsRules
     * @param PaymentContextInterface     $context
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getFilteredPaymentMethodsConfigsRules(
        array $paymentMethodsConfigsRules,
        PaymentContextInterface $context
    );
}
