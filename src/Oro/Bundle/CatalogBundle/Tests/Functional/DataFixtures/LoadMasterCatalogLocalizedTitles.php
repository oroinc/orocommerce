<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadMasterCatalogLocalizedTitles extends AbstractFixture implements DependentFixtureInterface
{
    public const MASTER_CATALOG_LOCALIZED_TITLES = 2;

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [LoadLocalizationData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $localizations = $manager->getRepository(Localization::class)->findAll();
        $category = $this->getCategory($manager);

        $title = new CategoryTitle();
        $title->setString('master');
        $title->setLocalization(reset($localizations));
        $category->addTitle($title);

        $manager->persist($title);
        $manager->flush();
    }

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
}
