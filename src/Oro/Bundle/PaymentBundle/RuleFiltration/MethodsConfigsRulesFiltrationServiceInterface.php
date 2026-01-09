<?php

namespace Oro\Bundle\PaymentBundle\RuleFiltration;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

/**
 * Defines the contract for filtering payment method configuration rules.
 *
 * Implementations filter a collection of payment method configuration rules based on
 * a payment context, returning only the rules applicable to the given context.
 */
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
