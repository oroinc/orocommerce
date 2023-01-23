<?php

namespace Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

/**
 * Represents a service to get payment method config rules.
 */
interface MethodsConfigsRulesByContextProviderInterface
{
    /**
     * @param PaymentContextInterface $context
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getPaymentMethodsConfigsRules(PaymentContextInterface $context): array;
}
