<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

class LoadPriceRules extends AbstractFixture implements DependentFixtureInterface
{
    const PRICE_RULE_1 = 'price_list_1_price_rule_1';

    /**
     * @var array
     */
    protected $data = [
        [
            'reference' => self::PRICE_RULE_1,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'productUnit' => LoadProductUnits::MILLILITER,
            'ruleCondition' => 'product.category.id == 1 and product.status == "enabled"',
            'rule' => 'product.msrp.value + 10',
            'priority' => 1,
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
            /** @var ProductUnit $unit */
            $unit = $this->getReference($priceRuleData['productUnit']);

            $priceRule
                ->setQuantity($priceRuleData['quantity'])
                ->setCurrency($priceRuleData['currency'])
                ->setPriceList($priceList)
                ->setProductUnit($unit)
                ->setRuleCondition($priceRuleData['ruleCondition'])
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
        return [LoadPriceLists::class, LoadProductUnits::class];
    }
}
