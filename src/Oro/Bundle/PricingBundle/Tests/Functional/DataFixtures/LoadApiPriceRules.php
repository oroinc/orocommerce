<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadApiPriceRules extends LoadPriceRules implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $data = [
        [
            'reference' => self::PRICE_RULE_1,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'productUnit' => LoadProductUnits::MILLILITER,
            'ruleCondition' => 'product.category.id == 1 and product.status == "enabled"',
            'rule' => 'pricelist[1].prices.value + 10',
            'priority' => 1,
        ],
        [
            'reference' => self::PRICE_RULE_2,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => LoadPriceLists::PRICE_LIST_2,
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'pricelist[%d].prices.value + 10',
            'priority' => 2,
            'parentPriceList' => LoadPriceLists::PRICE_LIST_1
        ],
        [
            'reference' => self::PRICE_RULE_3,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'pricelist[1].prices.value + 10',
            'priority' => 3,
        ],
        [
            'reference' => self::PRICE_RULE_4,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => LoadPriceLists::PRICE_LIST_2,
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'pricelist[1].prices.value + 10',
            'priority' => 4,
        ],
        [
            'reference' => self::PRICE_RULE_5,
            'quantity' => 2,
            'currency' => 'USD',
            'priceList' => LoadPriceLists::PRICE_LIST_4,
            'productUnit' => 'product_unit.milliliter',
            'ruleCondition' => 'product.category.id == 1',
            'rule' => 'pricelist[1].prices.value + 10',
            'priority' => 5,
        ]
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $priceRuleLexemeHandler = $this->container->get('oro_pricing.handler.price_rule_lexeme_handler');

        $priceLists = [];
        foreach (self::$data as $row) {
            $priceList = $this->getReference($row['priceList']);
            if (!empty($priceLists[$priceList->getId()])) {
                continue;
            }

            $priceLists[$priceList->getId()] = true;
            $priceRuleLexemeHandler->updateLexemesWithoutFlush($priceList);
        }

        $manager->flush();
    }
}
