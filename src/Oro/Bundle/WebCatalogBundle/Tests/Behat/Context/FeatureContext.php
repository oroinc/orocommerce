<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Behat\Context\FeatureContext as ConfigContext;
use Oro\Bundle\FormBundle\Tests\Behat\Context\FormContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private ?OroMainContext $oroMainContext = null;
    private ?ConfigContext $configContext = null;
    private ?FormContext $formContext = null;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
        $this->configContext = $environment->getContext(ConfigContext::class);
        $this->formContext = $environment->getContext(FormContext::class);
    }

    /**
     * @Given /^I set "(?P<webCatalogName>[\w\s]+)" as default web catalog$/
     *
     * @param string $webCatalogName
     */
    public function iSetDefaultWebCatalog($webCatalogName)
    {
        $this->oroMainContext->iOpenTheMenuAndClick('System/Configuration');
        $this->waitForAjax();
        $this->configContext->followLinkOnConfigurationSidebar('System Configuration/Websites/Routing');
        $this->waitForAjax();

        $this->formContext->uncheckUseDefaultForField('Web Catalog', 'Use default');
        $this->waitForAjax();

        $this->oroMainContext->fillField('WebCatalogSystemConfigSelect', $webCatalogName);
        $this->waitForAjax();
        $this->oroMainContext->pressButton('Save settings');
        $this->waitForAjax();
        $this->oroMainContext->iShouldSeeFlashMessage('Configuration saved');
    }

    /**
     * @Given /^(?:|I )set "(?P<webCatalogName>[\w\s]+)" as web catalog for "(?P<websiteName>[\w\s]+)" website$/
     */
    public function iSetWebsiteWebCatalog(string $webCatalogName, string $websiteName): void
    {
        $webCatalog = $this->getWebCatalog($webCatalogName);
        $scope = 'website';
        $scopeIdentifier = $this->getWebsite($websiteName);

        $rootContentNode = $this->getRootContentNode($webCatalog);
        /** @var ConfigManager $configManager */
        $configManager = $this->getAppContainer()->get('oro_config.' . $scope);
        $configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId(), $scopeIdentifier);
        if (null !== $rootContentNode) {
            $configManager->set('oro_web_catalog.navigation_root', $rootContentNode->getId(), $scopeIdentifier);
        }
        $configManager->flush();
    }

    private function getWebCatalog(string $webCatalogName): WebCatalog
    {
        $webCatalog = $this->getDoctrine()->getRepository(WebCatalog::class)
            ->findOneBy(['name' => $webCatalogName]);
        self::assertNotNull($webCatalog, sprintf('Web Catalog with name "%s" not found', $webCatalogName));

        return $webCatalog;
    }

    private function getRootContentNode(WebCatalog $webCatalog): ?ContentNode
    {
        return $this->getDoctrine()->getRepository(ContentNode::class)
            ->getRootNodeByWebCatalog($webCatalog);
    }

    private function getWebsite(string $websiteName): Website
    {
        $webCatalog = $this->getDoctrine()->getRepository(Website::class)
            ->findOneBy(['name' => $websiteName]);
        self::assertNotNull($webCatalog, sprintf('Website with name "%s" not found', $websiteName));

        return $webCatalog;
    }

    private function getDoctrine(): ManagerRegistry
    {
        return $this->getAppContainer()->get('doctrine');
    }
}
