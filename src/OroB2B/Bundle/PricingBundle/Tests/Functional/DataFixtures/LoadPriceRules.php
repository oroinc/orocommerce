<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;

class LoadPriceRules extends AbstractFixture implements DependentFixtureInterface
{
    const PRICE_RULE_1 = 'price_list_1_rule';

    /**
     * @var array
     */
    protected $data = [
        [
            'priceList' => 'price_list_1',
            'rule' => 'product.msrp.value + 10',
            'priority' => 100,
            'reference' => self::PRICE_RULE_1
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $priceRuleData) {
            $priceRule = new PriceRule();

            /** @var PriceList $priceList */
            $priceList = $this->getReference($priceRuleData['priceList']);
            $priceRule
                ->setPriceList($priceList)
                ->setRule($priceRuleData['rule'])
                ->setPriority($priceRuleData['priority']);

            $manager->persist($priceRule);
            $this->setReference($priceRuleData['reference'], $priceRule);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadPriceLists::class];
    }
}
