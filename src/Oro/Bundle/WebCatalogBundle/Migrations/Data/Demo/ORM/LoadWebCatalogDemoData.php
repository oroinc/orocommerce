<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData;
use Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM\LoadPageDemoData;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Web catalog demo data
 */
class LoadWebCatalogDemoData extends AbstractLoadWebCatalogDemoData implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const DEFAULT_WEB_CATALOG_NAME = 'Default Web Catalog';
    const DEFAULT_WEB_CATALOG_DESC= 'Default Web Catalog description';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryDemoData::class,
            LoadSegmentsForWebCatalogDemoData::class,
            LoadPageDemoData::class
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $webCatalog = $this->loadWebCatalogData($manager);
        $this->enableWebCatalog($webCatalog);
        $this->generateCache($webCatalog);
    }

    /**
     * @param ObjectManager $manager
     * @return WebCatalog
     */
    protected function loadWebCatalogData(ObjectManager $manager)
    {
        $webCatalog = $this->createCatalog($manager);

        $contentNodes =
            $this->getWebCatalogData('@OroWebCatalogBundle/Migrations/Data/Demo/ORM/data/web_catalog_data.yml');

        $this->loadContentNodes($manager, $webCatalog, $contentNodes);

        $manager->flush();

        return $webCatalog;
    }

    /**
     * @param WebCatalog $webCatalog
     */
    protected function enableWebCatalog(WebCatalog $webCatalog)
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(OroWebCatalogExtension::ALIAS . '.web_catalog', $webCatalog->getId());

        $configManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return WebCatalog
     */
    protected function createCatalog(ObjectManager $manager)
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
