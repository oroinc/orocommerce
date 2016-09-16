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
    const PRICE_RULE_2 = 'price_list_1_price_rule_2';
    const PRICE_RULE_3 = 'price_list_1_price_rule_3';
    const PRICE_RULE_4 = 'price_list_2_price_rule_4';
    const PRICE_RULE_5 = 'price_list_3_price_rule_5';

    /**
     * @var array
     */
    protected static $data = [
        [
            'reference' => self::PRICE_RULE_1,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => 'price_list_1',
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1 and product.status == "enabled"',
            'rule' => 'product.msrp.value + 10',
            'priority' => 1,
        ],
        [
            'reference' => self::PRICE_RULE_2,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => 'price_list_1',
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'product.msrp.value + 10',
            'priority' => 2,
        ],
        [
            'reference' => self::PRICE_RULE_3,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => 'price_list_1',
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'product.msrp.value + 10',
            'priority' => 3,
        ],
        [
            'reference' => self::PRICE_RULE_4,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => 'price_list_2',
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'product.msrp.value + 10',
            'priority' => 4,
        ],
        [
            'reference' => self::PRICE_RULE_5,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => 'price_list_4',
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'product.msrp.value + 10',
            'priority' => 5,
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$data as $priceRuleData) {
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
