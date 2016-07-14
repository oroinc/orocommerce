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
            'reference' => 'price_list_1_price_rule_1',
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => 'price_list_1',
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'Category.id == 1',
            'rule' => 'Product.msrp.value + 10',
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
