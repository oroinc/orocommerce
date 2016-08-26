<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\FlatRateShippingMethod;

class LoadShippingRuleDemoData extends AbstractFixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $typeConfig = new ShippingRuleMethodTypeConfig();
        $typeConfig->setType(FlatRateShippingMethod::DEFAULT_TYPE)
            // TODO: fix durring BB-4286
            ->setOptions([]);

        $methodConfig = new ShippingRuleMethodConfig();
        $methodConfig->setMethod(FlatRateShippingMethod::NAME)
            ->addTypeConfig($typeConfig);

        $shippingRule = new ShippingRule();
        $shippingRule->setName('Default')
            ->setCurrency('USD')
            ->setPriority(1)
            ->addMethodConfig($methodConfig);

        $manager->persist($shippingRule);
        $manager->flush();
    }
}
