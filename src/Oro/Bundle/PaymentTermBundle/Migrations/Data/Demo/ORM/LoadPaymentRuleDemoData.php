<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

class LoadPaymentRuleDemoData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType(PaymentTerm::TYPE);

        $rule = new Rule();
        $rule->setName('Default')
            ->setEnabled(true)
            ->setSortOrder(1);

        $shippingRule = new PaymentMethodsConfigsRule();
        $shippingRule->setRule($rule);
        $shippingRule->setCurrency('USD')
            ->addMethodConfig($methodConfig);

        $manager->persist($shippingRule);
        $manager->flush();
    }
}
