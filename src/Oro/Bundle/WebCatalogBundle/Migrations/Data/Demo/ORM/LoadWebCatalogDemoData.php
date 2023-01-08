<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryBasedSegmentsDemoData;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData;
use Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM\LoadPageDemoData;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * Loads web catalog demo data.
 */
class LoadWebCatalogDemoData extends AbstractLoadWebCatalogDemoData implements DependentFixtureInterface
{
    use UserUtilityTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadCategoryDemoData::class,
            LoadCategoryBasedSegmentsDemoData::class,
            LoadPageDemoData::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $webCatalog = $this->createCatalog($manager);
        $contentNodes = $this->getWebCatalogData(
            '@OroWebCatalogBundle/Migrations/Data/Demo/ORM/data/web_catalog_data.yml'
        );
        $this->loadContentNodes($manager, $webCatalog, $contentNodes);
        $manager->flush();

        $this->enableWebCatalog($webCatalog);
        $this->generateCache($webCatalog);
    }

    private function createCatalog(ObjectManager $manager): WebCatalog
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $webCatalog = new WebCatalog();
        $webCatalog->setName(self::DEFAULT_WEB_CATALOG_NAME);
        $webCatalog->setDescription(self::DEFAULT_WEB_CATALOG_DESC);
        $webCatalog->setOwner($businessUnit);
        $webCatalog->setOrganization($organization);

        $manager->persist($webCatalog);
        $manager->flush($webCatalog);

        return $webCatalog;
    }
}
