<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;

class LoadShippingRuleDemoData extends AbstractFixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $typeConfig = new ShippingRuleMethodTypeConfig();
        $typeConfig->setEnabled(true);
        $typeConfig->setType(FlatRateShippingMethodType::IDENTIFIER)
            ->setOptions([
                FlatRateShippingMethodType::PRICE_OPTION => 10,
                FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ORDER_TYPE,
            ]);

        $methodConfig = new ShippingRuleMethodConfig();
        $methodConfig->setMethod(FlatRateShippingMethod::IDENTIFIER)
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
