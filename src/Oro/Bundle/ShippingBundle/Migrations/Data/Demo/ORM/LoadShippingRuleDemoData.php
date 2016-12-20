<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;

class LoadShippingRuleDemoData extends AbstractFixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $typeConfig = new ShippingMethodTypeConfig();
        $typeConfig->setEnabled(true);
        $typeConfig->setType(FlatRateShippingMethodType::IDENTIFIER)
            ->setOptions([
                FlatRateShippingMethodType::PRICE_OPTION => 10,
                FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ORDER_TYPE,
            ]);

        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod(FlatRateShippingMethod::IDENTIFIER)
            ->addTypeConfig($typeConfig);

        $rule = new Rule();
        $rule->setName('Default')
            ->setSortOrder(1);

        $shippingRule = new ShippingMethodsConfigsRule();
        $shippingRule->setRule($rule);
        $shippingRule->setCurrency('USD')
            ->addMethodConfig($methodConfig);

        $manager->persist($shippingRule);
        $manager->flush();
    }
}
