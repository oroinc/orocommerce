<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadMasterCatalogLocalizedTitles extends AbstractFixture implements DependentFixtureInterface
{
    public const MASTER_CATALOG_LOCALIZED_TITLES = 2;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadLocalizationData::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
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
        return $manager->getRepository(Category::class)
            ->getMasterCatalogRootQueryBuilder()
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $this->getReference(LoadOrganization::ORGANIZATION))
            ->getQuery()
            ->getSingleResult();
    }
}
