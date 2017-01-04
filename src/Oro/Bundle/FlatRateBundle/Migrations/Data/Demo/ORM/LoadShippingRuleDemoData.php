<?php

namespace Oro\Bundle\FlatRateBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethodType;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;

class LoadShippingRuleDemoData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $typeConfig = new ShippingMethodTypeConfig();
        $typeConfig->setEnabled(true);
        $typeConfig->setType(FlatRateMethodType::IDENTIFIER)
            ->setOptions([
                FlatRateMethodType::PRICE_OPTION => 10,
                FlatRateMethodType::TYPE_OPTION => FlatRateMethodType::PER_ORDER_TYPE,
            ]);

        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod(FlatRateMethod::IDENTIFIER)
            ->addTypeConfig($typeConfig);

        $rule = new Rule();
        $rule->setName('Default')
            ->setEnabled(true)
            ->setSortOrder(1);

        $shippingRule = new ShippingMethodsConfigsRule();
        $shippingRule->setRule($rule);
        $shippingRule->setCurrency('USD')
            ->addMethodConfig($methodConfig);

        $manager->persist($shippingRule);
        $manager->flush();
    }
}
