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
            'reference'=> 'lexeme_1',
            'priceRule' => 'price_rule_1',
            'className' => 'class1',
            'fieldName' => 'field1',
        ],
        [
            'reference'=> 'lexeme_2',
            'priceRule' => 'price_rule_2',
            'className' => 'class1',
            'fieldName' => 'field1',
        ],
        [
            'reference'=> 'lexeme_3',
            'priceRule' => 'price_rule_3',
            'className' => 'class1',
            'fieldName' => 'field1',
        ],
        [
            'reference'=> 'lexeme_4',
            'priceRule' => 'price_rule_4',
            'className' => 'class1',
            'fieldName' => 'field1',
        ],
        [
            'reference'=> 'lexeme_5',
            'priceRule' => 'price_rule_5',
            'className' => 'class1',
            'fieldName' => 'field1',
        ],
        [
            'reference'=> 'lexeme_6',
            'priceRule' => 'price_rule_6',
            'className' => 'class1',
            'fieldName' => 'field1',
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
                ->setPriceRule($priceRule);

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
