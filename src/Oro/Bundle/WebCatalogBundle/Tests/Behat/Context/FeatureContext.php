<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\ConfigBundle\Tests\Behat\Context\FeatureContext as ConfigContext;
use Oro\Bundle\FormBundle\Tests\Behat\Context\FormContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

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
}
