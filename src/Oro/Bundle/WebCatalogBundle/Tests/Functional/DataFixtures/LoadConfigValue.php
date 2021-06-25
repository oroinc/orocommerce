<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadConfigValue extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(WebCatalogUsageProvider::SETTINGS_KEY, $webCatalog->getId());
        $configManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebCatalogData::class,
            LoadScopeData::class,
            LoadWebsiteData::class
        ];
    }
}
