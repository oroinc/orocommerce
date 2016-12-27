<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData
    as BasicLoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;

class LoadPaymentMethodsConfigsRuleData extends BasicLoadPaymentMethodsConfigsRuleData
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType(PaymentTerm::TYPE);

        /** @var PaymentMethodsConfigsRule $methodsConfigsRule */
        $methodsConfigsRule = $this->getReference('payment.payment_methods_configs_rule.1');
        $methodsConfigsRule->addMethodConfig($methodConfig);

        $manager->flush();
    }
}
