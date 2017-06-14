<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given /^I set "(?P<webCatalogName>[\w\s]+)" as default web catalog$/
     *
     * @param string $webCatalogName
     */
    public function setDefaultWebCatalog($webCatalogName)
    {
        $webCatalogRepository = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(WebCatalog::class);

        $webCatalog = $webCatalogRepository->findOneBy(['name' => $webCatalogName]);

        static::assertNotNull($webCatalog, sprintf('Web Catalog with name "%s" not found', $webCatalogName));

        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $configManager->flush();
    }
}
