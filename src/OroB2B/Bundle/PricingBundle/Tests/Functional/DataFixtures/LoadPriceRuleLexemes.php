<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;

class LoadPriceRuleLexemes extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'reference' => 'price_list_1_lexeme_1',
            'priceList' => 'price_list_1',
            'priceRule' => 'price_list_1_price_rule_1',
            'className' => 'OroB2B\Bundle\CatalogBundle\Entity\Category',
            'fieldName' => 'id',
        ],
        [
            'reference' => 'price_list_1_lexeme_2',
            'priceList' => 'price_list_1',
            'priceRule' => 'price_list_1_price_rule_1',
            'className' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
            'fieldName' => 'value',
            'reference_entity' => 'price_attribute_price_list_1'
        ]
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
                $lexemeEntity->setRelationId($this->getReference('price_list_1_price_rule_1')->getId());
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
        return [LoadPriceRules::class];
    }
}
