<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadPriceRuleLexemes extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'reference' => 'price_list_1_lexeme_1',
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'priceRule' => LoadPriceRules::PRICE_RULE_1,
            'className' => Category::class,
            'fieldName' => 'id',
        ],
        [
            'reference' => 'price_list_1_lexeme_2',
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'priceRule' => LoadPriceRules::PRICE_RULE_1,
            'className' => Product::class,
            'fieldName' => 'status',
        ],
        [
            'reference' => 'price_list_1_lexeme_3',
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'priceRule' => LoadPriceRules::PRICE_RULE_1,
            'className' => PriceAttributeProductPrice::class,
            'fieldName' => 'value',
            'reference_entity' => 'price_attribute_price_list_1'
        ],
        [
            'reference' => 'price_list_2_lexeme_1',
            'priceList' => LoadPriceLists::PRICE_LIST_2,
            'priceRule' => LoadPriceRules::PRICE_RULE_1,
            'className' => ProductPrice::class,
            'fieldName' => 'value',
            'reference_entity' => LoadPriceLists::PRICE_LIST_1
        ],
        [
            'reference' => 'price_list_2_lexeme_2',
            'priceList' => LoadPriceLists::PRICE_LIST_2,
            'priceRule' => null,
            'className' => PriceList::class,
            'fieldName' => 'assignedProducts',
            'reference_entity' => LoadPriceLists::PRICE_LIST_1
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $lexeme) {
            $lexemeEntity=new PriceRuleLexeme();
            $lexemeEntity
                ->setClassName($lexeme['className'])
                ->setFieldName($lexeme['fieldName'])
                ->setPriceList($this->getReference($lexeme['priceList']));
            
            if ($lexeme['priceRule']) {
                /** @var PriceRule $priceRule */
                $priceRule = $this->getReference($lexeme['priceRule']);
                $lexemeEntity->setPriceRule($priceRule);
            }

            if (isset($lexeme['reference_entity'])) {
                $lexemeEntity->setRelationId($this->getReference($lexeme['reference_entity'])->getId());
            }

            $manager->persist($lexemeEntity);
            $this->setReference($lexeme['reference'], $lexemeEntity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadPriceRules::class, LoadPriceAttributePriceLists::class];
    }
}
