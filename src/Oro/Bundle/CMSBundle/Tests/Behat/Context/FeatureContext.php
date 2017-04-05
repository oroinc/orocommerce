<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^(?:|I )fill in Landing Page Titles field with "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function fillInLandingPageTitlesFieldWith($value)
    {
        $productNameField = $this->createElement('LandingPageTitlesField');
        $productNameField->focus();
        $productNameField->setValue($value);
        $productNameField->blur();
        $this->waitForAjax();
    }

    /**
     *
     * @Then /^(?:|I )am on page with title "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $pageTitle
     */
    public function iAmOnPageWithTitle($pageTitle)
    {
        $currentPageTitle = $this->createElement('PageTitle');

        static::assertEquals(
            $pageTitle,
            $currentPageTitle->getHtml(),
            sprintf('Expected "%s" page title, actual title is "%s"', $pageTitle, $currentPageTitle->getHtml())
        );
    }
}
