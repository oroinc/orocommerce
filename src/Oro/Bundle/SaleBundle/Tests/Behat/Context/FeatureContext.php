<?php

namespace Oro\Bundle\SaleBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * @Given I set :poNumber value to PO Number field
     * @param string $poNumber
     */
    public function setValueToPONumberField($poNumber)
    {
        $this->getSession()->getPage()->fillField('PO Number', $poNumber);
    }

    /**
     * @Given Admin creates a quote for RFQ with PO Number :poNumber
     * @param string $poNumber
     */
    public function adminCreatesAQuoteForRFQWithPONumber($poNumber)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1880, 'current');

        $this->oroMainContext->loginAsUserWithPassword();
        $this->waitForAjax();
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('Sales/Requests For Quote');
        $this->waitForAjax();

        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($poNumber, 'View');
        $this->waitForAjax();

        $this->getSession()->getPage()->clickLink('Create Quote');
        $this->waitForAjax();

        $unitPrice = $this->getSession()->getPage()->findField(
            'oro_sale_quote[quoteProducts][0][quoteProductOffers][0][price][value]'
        );

        $unitPrice->focus();
        $unitPrice->setValue('5.0');
        $unitPrice->blur();

        $this->getSession()->getPage()->pressButton('Save and Close');

        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * @When Buyer is on quotes page
     */
    public function buyerIsOnQuotesPage()
    {
        $this->visitPath('customer/quote');
    }

    /**
     * @When Buyer starts checkout for a quote with :poNumber PO Number
     * @param string $poNumber
     */
    public function buyerStartsCheckoutForAQuoteWithPONumber($poNumber)
    {
        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($poNumber, 'View');
        $this->waitForAjax();

        $this->getSession()->getPage()->clickLink('Accept and Submit to Order');
        $this->waitForAjax();

        $this->getSession()->getPage()->pressButton('Submit');
        $this->waitForAjax();
    }

    /**
     * @Then Buyer is on enter billing information checkout step
     */
    public function buyerIsOnEnterBillingInformationCheckoutStep()
    {
        /** @var CheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Billing Information');
    }
}
