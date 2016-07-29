<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $lexeme) {
            /** @var PriceRule $priceRule */
            $priceRule = $this->getReference($lexeme['priceRule']);
            $lexemeEntity=new PriceRuleLexeme();
            $lexemeEntity
                ->setClassName($lexeme['className'])
                ->setFieldName($lexeme['fieldName'])
                ->setPriceList($this->getReference($lexeme['priceList']))
                ->setPriceRule($priceRule);

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
