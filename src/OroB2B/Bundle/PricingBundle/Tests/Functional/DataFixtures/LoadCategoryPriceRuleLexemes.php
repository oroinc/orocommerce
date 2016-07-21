<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;

class LoadCategoryPriceRuleLexemes extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            [
                'category' => $manager->getRepository(Category::class)->getMasterCatalogRoot(),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::SECOND_LEVEL2),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_2),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::FOURTH_LEVEL2),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_3),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::FIRST_LEVEL),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::SECOND_LEVEL1),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_4),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::THIRD_LEVEL1),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_5),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::THIRD_LEVEL1),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_5),
                'field' => 'createdAt',
            ],
        ];

        foreach ($data as $item) {
            $lexeme = new PriceRuleLexeme();
            /** @var Category $category */
            $category = $item['category'];
            $lexeme->setClassName(Category::class)
                ->setFieldName($item['field'])
                ->setPriceList($item['priceList'])
                ->setRelationId($category->getId());

            $manager->persist($lexeme);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryData::class,
        ];
    }
}
