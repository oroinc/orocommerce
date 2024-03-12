<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadCategoryPriceRuleLexemes extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadCategoryData::class, LoadPriceLists::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $category = $this->getCategory($manager);

        $data = [
            [
                'category' => $category,
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::SECOND_LEVEL2),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_2),
                'field' => 'id',
            ],
            [
                'category' => $this->getReference(LoadCategoryData::FIRST_LEVEL),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'field' => 'id',
            ],
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_3),
                'field' => 'createdAt',
            ],
        ];

        foreach ($data as $item) {
            $lexeme = new PriceRuleLexeme();
            $lexeme->setClassName(Category::class);
            $lexeme->setFieldName($item['field']);
            $lexeme->setPriceList($item['priceList']);
            if (\array_key_exists('category', $item)) {
                /** @var Category $category */
                $category = $item['category'];
                $lexeme->setRelationId($category->getId());
            }
            $manager->persist($lexeme);
        }
        $manager->flush();
    }

    private function getCategory(ObjectManager $manager): Category
    {
        return $manager->getRepository(Category::class)
            ->getMasterCatalogRootQueryBuilder()
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $this->getReference(LoadOrganization::ORGANIZATION))
            ->getQuery()
            ->getSingleResult();
    }
}
