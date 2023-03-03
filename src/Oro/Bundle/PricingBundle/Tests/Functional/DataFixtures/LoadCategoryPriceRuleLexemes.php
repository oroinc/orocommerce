<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;

class LoadCategoryPriceRuleLexemes extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
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
            $lexeme->setClassName(Category::class)
                ->setFieldName($item['field'])
                ->setPriceList($item['priceList']);

            if (array_key_exists('category', $item)) {
                /** @var Category $category */
                $category = $item['category'];
                $lexeme->setRelationId($category->getId());
            }

            $manager->persist($lexeme);
        }

        $manager->flush();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCategory(ObjectManager $manager): Category
    {
        /** @var Organization $organization */
        $organization = $manager->getRepository(Organization::class)->getFirst();

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository(Category::class);
        $queryBuilder = $categoryRepository->getMasterCatalogRootQueryBuilder();
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryData::class,
            LoadPriceLists::class
        ];
    }
}
