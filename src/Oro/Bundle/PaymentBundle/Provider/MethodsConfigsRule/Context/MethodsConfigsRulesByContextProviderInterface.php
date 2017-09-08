<?php

namespace Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

interface MethodsConfigsRulesByContextProviderInterface
{
    /**
     * @param PaymentContextInterface $context
     * @return array|PaymentMethodsConfigsRule[]
     */
    public function getPaymentMethodsConfigsRules(PaymentContextInterface $context);
}
