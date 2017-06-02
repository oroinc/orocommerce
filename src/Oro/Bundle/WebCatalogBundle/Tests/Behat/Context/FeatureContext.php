<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SystemConfigForm;
use Oro\Bundle\FormBundle\Tests\Behat\Context\FormContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\ConfigBundle\Tests\Behat\Context\FeatureContext as ConfigContext;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @var ConfigContext
     */
    private $configContext;

    /**
     * @var FormContext
     */
    private $formContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
        $this->formContext = $environment->getContext(FormContext::class);
        $this->configContext = $environment->getContext(ConfigContext::class);
    }

    /**
     * @Given /^I set "(?P<webCatalogName>[\w\s]+)" as default web catalog$/
     *
     * @param string $webCatalogName
     */
    public function setDefaultWebCatalog($webCatalogName)
    {
        $this->oroMainContext->iOpenTheMenuAndClick('System/ Configuration');
        $this->waitForAjax();
        $this->configContext->clickLinkOnConfigurationSidebar('Websites');
        $this->configContext->clickLinkOnConfigurationSidebar('Routing');
        $this->waitForAjax();

        /** @var SystemConfigForm $systemConfigForm */
        $systemConfigForm = $this->oroMainContext->createElement('System Config Form');
        $systemConfigForm->uncheckUseDefaultCheckbox('Web Catalog');
        $this->oroMainContext->fillField('Web Catalog System Config Select', $webCatalogName);

        $this->formContext->iSaveForm();
        $this->oroMainContext->iShouldSeeFlashMessage('Configuration saved');
    }
}
