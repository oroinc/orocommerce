<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadMasterCatalogLocalizedTitles extends AbstractFixture implements DependentFixtureInterface
{
    const MASTER_CATALOG_LOCALIZED_TITLES = 2;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $localizations = $manager->getRepository('OroLocaleBundle:Localization')->findAll();
        $category = $this->getCategory($manager);

        $title = new CategoryTitle();
        $title->setString('master');
        $title->setLocalization(reset($localizations));
        $category->addTitle($title);

        $manager->persist($title);
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
        $categoryRepository = $manager->getRepository('OroCatalogBundle:Category');
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
        return [LoadLocalizationData::class];
    }
}
