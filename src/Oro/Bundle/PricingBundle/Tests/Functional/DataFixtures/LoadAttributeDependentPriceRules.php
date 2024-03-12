<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadAttributeDependentPriceRules extends LoadPriceRules implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array[]
     */
    protected static $data = [
        [
            'reference' => self::PRICE_RULE_1,
            'quantity' => 1,
            'currency' => 'USD',
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'productUnit' => LoadProductUnits::MILLILITER,
            'ruleCondition' => null,
            'rule' => 'product.price_attribute_price_list_1.value + 10',
            'priority' => 1,
        ],
    ];

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

    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            [LoadPriceAttributePriceLists::class]
        );
    }
}
