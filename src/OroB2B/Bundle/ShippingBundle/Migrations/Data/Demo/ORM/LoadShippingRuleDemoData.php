<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Method\FlatRateShippingMethod;

class LoadShippingRuleDemoData extends AbstractFixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $flatRate = new FlatRateRuleConfiguration();
        $flatRate->setMethod(FlatRateShippingMethod::NAME)
            ->setType(FlatRateShippingMethod::NAME)
            ->setValue(10)
            ->setProcessingType(FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER);

        $shippingRule = new ShippingRule();
        $shippingRule->setName('Default')
            ->setCurrency('USD')
            ->setPriority(1)
            ->addConfiguration($flatRate);

        $manager->persist($shippingRule);
        $manager->flush();
    }
}
