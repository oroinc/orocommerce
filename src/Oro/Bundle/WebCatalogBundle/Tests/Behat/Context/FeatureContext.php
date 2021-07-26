<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class FeatureContext extends OroFeatureContext
{
    private ConfigManager $configManager;

    private DoctrineHelper $doctrineHelper;

    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @Given /^I set "(?P<webCatalogName>[\w\s]+)" as default web catalog$/
     *
     * @param string $webCatalogName
     */
    public function setDefaultWebCatalog($webCatalogName)
    {
        $webCatalogRepository = $this->doctrineHelper->getEntityRepository(WebCatalog::class);

        $webCatalog = $webCatalogRepository->findOneBy(['name' => $webCatalogName]);

        static::assertNotNull($webCatalog, sprintf('Web Catalog with name "%s" not found', $webCatalogName));

        $this->configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $this->configManager->flush();
    }
}
