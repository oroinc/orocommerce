<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class FeatureContext extends OroFeatureContext
{
    /**
     * @Given /^I set "(?P<webCatalogName>[\w\s]+)" as default web catalog$/
     *
     * @param string $webCatalogName
     */
    public function setDefaultWebCatalog($webCatalogName)
    {
        $webCatalogRepository = $this->getAppContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(WebCatalog::class);

        $webCatalog = $webCatalogRepository->findOneBy(['name' => $webCatalogName]);

        static::assertNotNull($webCatalog, sprintf('Web Catalog with name "%s" not found', $webCatalogName));

        /** @var ConfigManager $configManager */
        $configManager = $this->getAppContainer()->get('oro_config.global');
        $configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $configManager->flush();
    }
}
