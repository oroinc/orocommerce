<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
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
        /** @var Organization $organization */
        $organization = $manager->getRepository(Organization::class)->getFirst();
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository('OroCatalogBundle:Category');
        $root               = $categoryRepository->getMasterCatalogRoot($organization);
        $localizations      = $manager->getRepository('OroLocaleBundle:Localization')->findAll();

        $title = new LocalizedFallbackValue();
        $title->setString('master');
        $title->setLocalization(reset($localizations));
        $root->addTitle($title);

        $manager->persist($title);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadLocalizationData::class];
    }
}
