<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

class LoadPriceRules extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'reference' => 'price_rule_1',
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => 'price_list_1',
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'rule_condition1',
            'rule' => 'rule1',
            'priority' => 1,
        ],
        [
            'reference' => 'price_rule_2',
            'quantity' => 42,
            'currency' => 'USD',
            'priceList' => 'price_list_2',
            'productUnit' => 'product_unit.liter',
            'ruleCondition' => 'rule_condition2',
            'rule' => 'rule2',
            'priority' => 2,
        ],
        [
            'reference' => 'price_rule_3',
            'quantity' => 3,
            'currency' => 'CAD',
            'priceList' => 'price_list_3',
            'productUnit' => 'product_unit.bottle',
            'ruleCondition' => 'rule_condition3',
            'rule' => 'rule3',
            'priority' => 3,
        ],
        [
            'reference' => 'price_rule_4',
            'quantity' => 1,
            'currency' => 'GBP',
            'priceList' => 'price_list_4',
            'productUnit' => 'product_unit.box',
            'ruleCondition' => 'rule_condition4',
            'rule' => 'rule4',
            'priority' => 4,
        ],
        [
            'reference' => 'price_rule_5',
            'quantity' => 1,
            'currency' => 'EUR',
            'priceList' => 'price_list_5',
            'productUnit' => 'product_unit.liter',
            'ruleCondition' => 'rule_condition5',
            'rule' => 'rule5',
            'priority' => 5,
        ],
        [
            'reference' => 'price_rule_6',
            'quantity' => 5,
            'currency' => 'USD',
            'priceList' => 'price_list_6',
            'productUnit' => 'product_unit.box',
            'ruleCondition' => 'rule_condition6',
            'rule' => 'rule6',
            'priority' => 6,
        ],
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
